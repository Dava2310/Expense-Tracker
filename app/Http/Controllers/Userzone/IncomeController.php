<?php

namespace App\Http\Controllers\Userzone;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreIncomeRequest;
use App\Http\Requests\UpdateIncomeRequest;
use App\Models\Account;
use App\Models\Income;
use App\Support\PeriodFilter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class IncomeController extends Controller
{
    /***
     * Lists the incomes received into the logged-in user's accounts, grouped by month
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

        $filteredIncomes = fn () => $user->incomes()
            ->when($accountId, fn ($query) => $query->where('incomes.account_id', $accountId))
            ->when($categoryId, fn ($query) => $query->where('incomes.category_id', $categoryId));

        $incomes = $filteredIncomes()
            ->with(['account', 'category'])
            ->when($period->start(), fn ($query, $start) => $query->where('incomes.date', '>=', $start))
            ->when($period->end(), fn ($query, $end) => $query->where('incomes.date', '<=', $end))
            ->orderByDesc('incomes.date')
            ->orderByDesc('incomes.id')
            ->get();

        $groups = $incomes
            ->groupBy(fn (Income $income): string => $income->date->format('Y-m'))
            ->map(fn ($items): array => [
                'title' => $items->first()->date->format('F Y'),
                'count' => $items->count(),
                'total' => $items->sum(fn (Income $income): float => (float) $income->amount),
                'incomes' => $items,
            ])
            ->values();

        $accounts = $user->accounts()->withTrashed()->orderBy('name')->get(['id', 'name', 'deleted_at']);
        $categories = $user->categories()->orderBy('name')->get(['id', 'name']);

        $scope = collect([
            $accountId ? 'into '.$accounts->firstWhere('id', $accountId)->name : null,
            $categoryId ? 'from '.$categories->firstWhere('id', $categoryId)->name : null,
        ])->filter()->whenEmpty(fn ($parts) => $parts->push('across all accounts'))->implode(' ');

        return view('userzone.incomes.index', [
            'groups' => $groups,
            'total' => $incomes->sum(fn (Income $income): float => (float) $income->amount),
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
            'outsideCount' => $filteredIncomes()->count() - $incomes->count(),
        ]);
    }

    /***
     * Renders the form to register a new income
     *
     * @param Request $request
     * @return View
     */
    public function create(Request $request): View
    {
        return view('userzone.incomes.create', [
            'accounts' => $request->user()->accounts()->orderBy('name')->get(['id', 'name', 'balance']),
            'categories' => $request->user()->categories()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    /***
     * Handles the creation of the new Income and adds its amount to the account balance
     *
     * @param StoreIncomeRequest $request
     * @return RedirectResponse
     */
    public function store(StoreIncomeRequest $request): RedirectResponse
    {
        $data = $request->validated();

        // The income and the balance update succeed or fail together
        DB::transaction(function () use ($data): void {
            Income::create($data);

            Account::whereKey($data['account_id'])->increment('balance', $data['amount']);
        });

        return redirect()->route('incomes.index')->with('status', 'income-created');
    }

    /***
     * Shows one of the user's incomes and the form to edit its concept
     *
     * @param Request $request
     * @param Income $income
     * @return View
     */
    public function edit(Request $request, Income $income): View
    {
        $this->ensureOwnedBy($request, $income);

        return view('userzone.incomes.edit', [
            'income' => $income->load(['account', 'category']),
        ]);
    }

    /***
     * Handles the update of an income (only its concept can be changed)
     *
     * @param UpdateIncomeRequest $request
     * @param Income $income
     * @return RedirectResponse
     */
    public function update(UpdateIncomeRequest $request, Income $income): RedirectResponse
    {
        $income->update($request->validated());

        return redirect()->route('incomes.index')->with('status', 'income-updated');
    }

    /**
     * Incomes that belong to somebody else's account are reported as not found.
     */
    private function ensureOwnedBy(Request $request, Income $income): void
    {
        abort_unless($income->account->user_id === $request->user()->id, 404);
    }
}
