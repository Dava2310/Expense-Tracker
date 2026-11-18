<?php

namespace App\Http\Controllers\Userzone;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreExpenseRequest;
use App\Http\Requests\UpdateExpenseRequest;
use App\Models\Account;
use App\Models\Expense;
use App\Support\PeriodFilter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ExpenseController extends Controller
{
    /***
     * Lists the expenses paid from the logged-in user's accounts, grouped by month
     * and optionally filtered by account, category and period
     *
     * @param Request $request
     * @return View
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        // Unknown ids (or accounts that belong to somebody else) are ignored
        $accountId = $user->accounts()->withTrashed()->whereKey($request->integer('account'))->value('id');
        $categoryId = $user->categories()->whereKey($request->integer('category'))->value('id');

        $period = PeriodFilter::fromRequest($request);

        $filteredExpenses = fn () => $user->expenses()
            ->when($accountId, fn ($query) => $query->where('expenses.account_id', $accountId))
            ->when($categoryId, fn ($query) => $query->where('expenses.category_id', $categoryId));

        $expenses = $filteredExpenses()
            ->with(['account.currency', 'category'])
            ->when($period->start(), fn ($query, $start) => $query->where('expenses.date', '>=', $start))
            ->when($period->end(), fn ($query, $end) => $query->where('expenses.date', '<=', $end))
            ->orderByDesc('expenses.date')
            ->orderByDesc('expenses.id')
            ->get();

        $groups = $expenses
            ->groupBy(fn (Expense $expense): string => $expense->date->format('Y-m'))
            ->map(fn ($items): array => [
                'title' => $items->first()->date->format('F Y'),
                'count' => $items->count(),
                'expenses' => $items,
            ])
            ->values();

        $accounts = $user->accounts()->withTrashed()->orderBy('name')->get(['id', 'name', 'deleted_at']);
        $categories = $user->categories()->orderBy('name')->get(['id', 'name']);

        $scope = collect([
            $accountId ? 'from '.$accounts->firstWhere('id', $accountId)->name : null,
            $categoryId ? 'on '.$categories->firstWhere('id', $categoryId)->name : null,
        ])->filter()->whenEmpty(fn ($parts) => $parts->push('across all accounts'))->implode(' ');

        return view('userzone.expenses.index', [
            'groups' => $groups,
            'periodPhrase' => $period->phrase(),
            'scope' => $scope,
            'accounts' => $accounts,
            'categories' => $categories,
            'accountId' => $accountId,
            'categoryId' => $categoryId,
            'periods' => PeriodFilter::PERIODS,
            'period' => $period->name,
            'from' => $period->from?->format('Y-m-d'),
            'to' => $period->to?->format('Y-m-d'),
            'activeFilters' => collect([$accountId, $categoryId, $period->isActive()])->filter()->count(),
            'outsideCount' => $filteredExpenses()->count() - $expenses->count(),
        ]);
    }

    /***
     * Renders the form to register a new expense
     *
     * @param Request $request
     * @return View
     */
    public function create(Request $request): View
    {
        return view('userzone.expenses.create', [
            'accounts' => $request->user()->accounts()->with('currency')->orderBy('name')->get(['id', 'name', 'balance', 'currency_id']),
            'categories' => $request->user()->categories()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    /***
     * Handles the creation of the new Expense and deducts its amount from the account balance
     *
     * @param StoreExpenseRequest $request
     * @return RedirectResponse
     */
    public function store(StoreExpenseRequest $request): RedirectResponse
    {
        $data = $request->validated();

        // The expense and the balance update succeed or fail together
        DB::transaction(function () use ($data): void {
            Expense::create($data);

            Account::whereKey($data['account_id'])->decrement('balance', $data['amount']);
        });

        return redirect()->route('expenses.index')->with('status', 'expense-created');
    }

    /***
     * Shows one of the user's expenses and the form to edit its concept
     *
     * @param Request $request
     * @param Expense $expense
     * @return View
     */
    public function edit(Request $request, Expense $expense): View
    {
        $this->ensureOwnedBy($request, $expense);

        return view('userzone.expenses.edit', [
            'expense' => $expense->load(['account.currency', 'category']),
        ]);
    }

    /***
     * Handles the update of an expense (only its concept can be changed)
     *
     * @param UpdateExpenseRequest $request
     * @param Expense $expense
     * @return RedirectResponse
     */
    public function update(UpdateExpenseRequest $request, Expense $expense): RedirectResponse
    {
        $expense->update($request->validated());

        return redirect()->route('expenses.index')->with('status', 'expense-updated');
    }

    /**
     * Expenses that belong to somebody else's account are reported as not found.
     */
    private function ensureOwnedBy(Request $request, Expense $expense): void
    {
        abort_unless($expense->account->user_id === $request->user()->id, 404);
    }
}
