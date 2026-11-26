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
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Handles listing, registering, correcting and deleting the user's expenses.
 */
class ExpenseController extends Controller
{
    /**
     * Lists the expenses grouped by month, filtered by account, category and period.
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
            ->select('expenses.id', 'expenses.amount_in_cents', 'expenses.concept', 'expenses.date', 'expenses.account_id', 'expenses.category_id')
            ->with(['account:id,name,currency_id', 'account.currency:id,symbol', 'category:id,name'])
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

    /**
     * Renders the form to register a new expense
     */
    public function create(Request $request): View
    {
        Gate::authorize('create', Expense::class);

        $accounts = $request->user()->accounts()->with('currency')->orderBy('name')->get(['id', 'name', 'balance_in_cents', 'currency_id']);
        $categories = $request->user()->categories()->orderBy('name')->get(['id', 'name']);

        return view('userzone.expenses.create', [
            'accounts' => $accounts,
            'categories' => $categories,
        ]);
    }

    /**
     * Creates the expense and deducts its amount from the account in one transaction.
     */
    public function store(StoreExpenseRequest $request): RedirectResponse
    {
        Gate::authorize('create', Expense::class);

        $data = $request->validated();

        // The expense and the balance update succeed or fail together
        DB::transaction(function () use ($data): void {
            Expense::create($data);

            Account::findOrFail($data['account_id'])->withdraw($data['amount']);
        });

        return redirect()->route('expenses.index')->with('status', 'expense-created');
    }

    /**
     * Shows the edit form; the expense's own account is offered even if deactivated.
     */
    public function edit(Request $request, Expense $expense): View
    {
        Gate::authorize('update', $expense);

        $user = $request->user();

        // The active accounts, plus the account the expense already belongs to even if it was deactivated
        $accounts = $user->accounts()->withTrashed()
            ->where(fn ($query) => $query->whereNull('deleted_at')->orWhere('id', $expense->account_id))
            ->with('currency:id,symbol')
            ->orderBy('name')
            ->get(['id', 'name', 'balance_in_cents', 'currency_id']);
        $categories = $user->categories()->orderBy('name')->get(['id', 'name']);
        $expense->loadMissing('account.currency:id,symbol');

        return view('userzone.expenses.edit', [
            'expense' => $expense,
            'accounts' => $accounts,
            'categories' => $categories,
        ]);
    }

    /**
     * Updates the expense and its balances, refusing a change that would leave an account negative.
     */
    public function update(UpdateExpenseRequest $request, Expense $expense): RedirectResponse
    {
        Gate::authorize('update', $expense);

        $data = $request->validated();
        $account = Account::withTrashed()->findOrFail($data['account_id']);

        // Refuse the change if the account does not have enough balance for it
        if (! $expense->canBeChangedTo($account, $data['amount'])) {
            return back()->withInput()->withErrors(['amount' => $account->name.' doesn’t have enough balance for this amount.']);
        }

        $expense->updateAndSyncBalance($data);

        return redirect()->route('expenses.index')->with('status', 'expense-updated');
    }

    /**
     * Deletes the expense and gives its amount back to the account.
     */
    public function destroy(Expense $expense): RedirectResponse
    {
        Gate::authorize('delete', $expense);

        $expense->deleteAndRestoreBalance();

        return redirect()->route('expenses.index')->with('status', 'expense-deleted');
    }
}
