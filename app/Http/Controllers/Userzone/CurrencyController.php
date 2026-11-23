<?php

namespace App\Http\Controllers\Userzone;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCurrencyRequest;
use App\Http\Requests\UpdateCurrencyRequest;
use App\Models\Account;
use App\Models\Currency;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CurrencyController extends Controller
{
    /***
     * Lists the currencies of the logged-in user with the number of accounts using each one,
     * counting inactive accounts too
     *
     * @param Request $request
     * @return View
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

    /***
     * Renders the form to create a new currency
     *
     * @return View
     */
    public function create(): View
    {
        return view('userzone.currencies.create');
    }

    /***
     * Handles the creation of the new currency
     *
     * @param StoreCurrencyRequest $request
     * @return RedirectResponse
     */
    public function store(StoreCurrencyRequest $request): RedirectResponse
    {
        $request->user()->currencies()->create($request->validated());

        return redirect()->route('currencies.index')->with('status', 'currency-created');
    }

    /***
     * Shows one of the user's currencies with the accounts that use it (active and inactive),
     * their counts and their total balance
     *
     * @param Request $request
     * @param Currency $currency
     * @return View
     */
    public function show(Request $request, Currency $currency): View
    {
        $this->ensureOwnedBy($request, $currency);

        $accounts = $request->user()->accounts()->withTrashed()->where('currency_id', $currency->id)->get();
        $accountsCount = $accounts->count();
        $activeAccountsCount = $accounts->filter(fn (Account $account): bool => ! $account->trashed())->count();
        $inactiveAccountsCount = $accounts->filter(fn (Account $account): bool => $account->trashed())->count();
        $isInUse = $accountsCount > 0;
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

    /***
     * Renders the form to edit one of the user's currencies
     *
     * @param Request $request
     * @param Currency $currency
     * @return View
     */
    public function edit(Request $request, Currency $currency): View
    {
        $this->ensureOwnedBy($request, $currency);

        $accountsCount = $currency->accounts()->withTrashed()->count();

        return view('userzone.currencies.edit', [
            'currency' => $currency,
            'accountsCount' => $accountsCount,
        ]);
    }

    /***
     * Handles the update of a currency
     *
     * @param UpdateCurrencyRequest $request
     * @param Currency $currency
     * @return RedirectResponse
     */
    public function update(UpdateCurrencyRequest $request, Currency $currency): RedirectResponse
    {
        $this->ensureOwnedBy($request, $currency);

        $currency->update($request->validated());

        return redirect()->route('currencies.index')->with('status', 'currency-updated');
    }

    /***
     * Deletes a currency. A currency used by any account, active or inactive, can't be deleted
     *
     * @param Request $request
     * @param Currency $currency
     * @return RedirectResponse
     */
    public function destroy(Request $request, Currency $currency): RedirectResponse
    {
        $this->ensureOwnedBy($request, $currency);

        // If currency has at least one account assigned to it, it can not be deleted
        if (! $currency->canBeDeleted()) {
            return redirect()->route('currencies.index');
        }

        $currency->delete();

        return redirect()->route('currencies.index')->with('status', 'currency-deleted');
    }

    /***
     * Reports a currency that belongs to somebody else as not found
     *
     * @param Request $request
     * @param Currency $currency
     * @return void
     */
    private function ensureOwnedBy(Request $request, Currency $currency): void
    {
        abort_unless($currency->user_id === $request->user()->id, 404);
    }
}
