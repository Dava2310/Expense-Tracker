<?php

namespace App\Http\Controllers\Userzone;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCurrencyRequest;
use App\Http\Requests\UpdateCurrencyRequest;
use App\Models\Account;
use App\Models\Currency;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Handles listing, creating, showing, editing and deleting the user's currencies.
 */
class CurrencyController extends Controller
{
    /**
     * Lists the user's currencies with the number of accounts using each one.
     */
    public function index(Request $request): View
    {
        $currencies = $request->user()->currencies()
            ->select('id', 'name', 'symbol')
            ->withCount(['accounts' => fn ($query) => $query->withTrashed()])
            ->latest()
            ->get();

        return view('userzone.currencies.index', compact('currencies'));
    }

    /**
     * Renders the form to create a new currency
     */
    public function create(): View
    {
        Gate::authorize('create', Currency::class);

        return view('userzone.currencies.create');
    }

    /**
     * Handles the creation of the new currency
     */
    public function store(StoreCurrencyRequest $request): RedirectResponse
    {
        Gate::authorize('create', Currency::class);

        $request->user()->currencies()->create($request->validated());

        return redirect()->route('currencies.index')->with('status', 'currency-created');
    }

    /**
     * Shows a currency with its accounts, their counts and their total balance.
     */
    public function show(Request $request, Currency $currency): View
    {
        Gate::authorize('view', $currency);

        // One query: the user's accounts in this currency, deactivated ones included
        $accounts = $request->user()->accounts()->withTrashed()
            ->select('id', 'name', 'bank', 'balance_in_cents', 'min_balance_in_cents', 'deleted_at')
            ->where('currency_id', $currency->id)
            ->get();

        // The counts are taken from the loaded collection, with no extra queries
        $accountsCount = $accounts->count();
        $activeAccountsCount = $accounts->filter(fn (Account $account): bool => ! $account->trashed())->count();
        $inactiveAccountsCount = $accounts->filter(fn (Account $account): bool => $account->trashed())->count();
        $isInUse = $accountsCount > 0;

        // A second query: the database sums the balances of every account in this currency
        $totalBalance = $currency->symbol.number_format($currency->totalBalance(), 2);

        return view('userzone.currencies.show', [
            'currency' => $currency,
            'accounts' => $accounts,
            'accountsCount' => $accountsCount,
            'activeAccountsCount' => $activeAccountsCount,
            'inactiveAccountsCount' => $inactiveAccountsCount,
            'isInUse' => $isInUse,
            'totalBalance' => $totalBalance,
        ]);
    }

    /**
     * Renders the form to edit one of the user's currencies
     */
    public function edit(Currency $currency): View
    {
        Gate::authorize('update', $currency);

        $accountsCount = $currency->accounts()->withTrashed()->count();

        return view('userzone.currencies.edit', [
            'currency' => $currency,
            'accountsCount' => $accountsCount,
        ]);
    }

    /**
     * Handles the update of a currency
     */
    public function update(UpdateCurrencyRequest $request, Currency $currency): RedirectResponse
    {
        Gate::authorize('update', $currency);

        $currency->update($request->validated());

        return redirect()->route('currencies.index')->with('status', 'currency-updated');
    }

    /**
     * Deletes a currency unless an account, active or inactive, uses it.
     */
    public function destroy(Currency $currency): RedirectResponse
    {
        Gate::authorize('delete', $currency);

        // If currency has at least one account assigned to it, it can not be deleted
        if (! $currency->canBeDeleted()) {
            return redirect()->route('currencies.index');
        }

        $currency->delete();

        return redirect()->route('currencies.index')->with('status', 'currency-deleted');
    }
}
