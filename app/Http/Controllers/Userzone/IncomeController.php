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
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Handles listing, registering, correcting and deleting the user's incomes.
 */
class IncomeController extends Controller
{
    /**
     * Lists the incomes grouped by month, filtered by account, category and period.
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
            ->select('incomes.id', 'incomes.amount_in_cents', 'incomes.concept', 'incomes.date', 'incomes.account_id', 'incomes.category_id')
            ->with(['account:id,name,currency_id', 'account.currency:id,symbol', 'category:id,name'])
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

    /**
     * Renders the form to register a new income
     */
    public function create(Request $request): View
    {
        Gate::authorize('create', Income::class);

        $accounts = $request->user()->accounts()->with('currency')->orderBy('name')->get(['id', 'name', 'balance_in_cents', 'currency_id']);
        $categories = $request->user()->categories()->orderBy('name')->get(['id', 'name']);

        return view('userzone.incomes.create', [
            'accounts' => $accounts,
            'categories' => $categories,
        ]);
    }

    /**
     * Creates the income and adds its amount to the account in one transaction.
     */
    public function store(StoreIncomeRequest $request): RedirectResponse
    {
        Gate::authorize('create', Income::class);

        $data = $request->validated();

        // The income and the balance update succeed or fail together
        DB::transaction(function () use ($data): void {
            Income::create($data);

            Account::findOrFail($data['account_id'])->deposit($data['amount']);
        });

        return redirect()->route('incomes.index')->with('status', 'income-created');
    }

    /**
     * Shows the edit form; the income's own account is offered even if deactivated.
     */
    public function edit(Request $request, Income $income): View
    {
        Gate::authorize('update', $income);

        $user = $request->user();

        // The active accounts, plus the account the income already belongs to even if it was deactivated
        $accounts = $user->accounts()->withTrashed()
            ->where(fn ($query) => $query->whereNull('deleted_at')->orWhere('id', $income->account_id))
            ->with('currency:id,symbol')
            ->orderBy('name')
            ->get(['id', 'name', 'balance_in_cents', 'currency_id']);
        $categories = $user->categories()->orderBy('name')->get(['id', 'name']);
        $income->loadMissing('account.currency:id,symbol');

        return view('userzone.incomes.edit', [
            'income' => $income,
            'accounts' => $accounts,
            'categories' => $categories,
        ]);
    }

    /**
     * Updates the income and its balances, refusing a change that would leave an account negative.
     */
    public function update(UpdateIncomeRequest $request, Income $income): RedirectResponse
    {
        Gate::authorize('update', $income);

        $data = $request->validated();
        $account = Account::withTrashed()->findOrFail($data['account_id']);

        // Refuse the change if it would leave the account below zero
        if (! $income->canBeChangedTo($account, $data['amount'])) {
            return back()->withInput()->withErrors(['amount' => $income->account->name.' would end up with a negative balance.']);
        }

        $income->updateAndSyncBalance($data);

        return redirect()->route('incomes.index')->with('status', 'income-updated');
    }

    /**
     * Deletes the income and takes its amount out, unless the account would go below zero.
     */
    public function destroy(Income $income): RedirectResponse
    {
        Gate::authorize('delete', $income);

        if (! $income->canBeDeleted()) {
            return redirect()->route('incomes.index')
                ->withErrors(['income' => $income->account->name.' would end up with a negative balance, so this income can’t be deleted.']);
        }

        $income->deleteAndRestoreBalance();

        return redirect()->route('incomes.index')->with('status', 'income-deleted');
    }
}
