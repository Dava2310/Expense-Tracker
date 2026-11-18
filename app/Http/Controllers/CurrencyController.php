<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCurrencyRequest;
use App\Http\Requests\UpdateCurrencyRequest;
use App\Models\Account;
use App\Models\Currency;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CurrencyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $currencies = $request->user()->currencies()
            ->withCount(['accounts' => fn ($query) => $query->withTrashed()])
            ->latest()
            ->get();

        return view('currencies.index', compact('currencies'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('currencies.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCurrencyRequest $request)
    {
        $request->user()->currencies()->create($request->validated());

        return redirect()->route('currencies.index')->with('status', 'currency-created');
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Currency $currency)
    {
        $this->ensureOwnedBy($request, $currency);

        $accounts = $request->user()->accounts()->withTrashed()->where('currency_id', $currency->id)->get();
        $accountsCount = $accounts->count();
        $activeAccountsCount = $accounts->filter(fn (Account $account): bool => ! $account->trashed())->count();
        $inactiveAccountsCount = $accounts->filter(fn (Account $account): bool => $account->trashed())->count();
        $isInUse = $accountsCount > 0;
        $totalBalance = $currency->symbol.number_format((float) $accounts->sum('balance'), 2);

        return view('currencies.show', [
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
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, Currency $currency)
    {
        $this->ensureOwnedBy($request, $currency);

        $accountsCount = $currency->accounts()->withTrashed()->count();

        return view('currencies.edit', [
            'currency' => $currency,
            'accountsCount' => $accountsCount,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCurrencyRequest $request, Currency $currency)
    {
        $this->ensureOwnedBy($request, $currency);

        $currency->update($request->validated());

        return redirect()->route('currencies.index')->with('status', 'currency-updated');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Currency $currency)
    {
        $this->ensureOwnedBy($request, $currency);

        // If currency has at least one account assigned to it, it can not be deleted
        if (!$currency->canBeDeleted())
        {
            return redirect()->route('currencies.index');
        }

        $currency->delete();

        return redirect()->route('currencies.index')->with('status', 'currency-deleted');
    }

    /**
     * Accounts that belong to somebody else are reported as not found.
     */
    private function ensureOwnedBy(Request $request, Currency $currency): void
    {
        abort_unless($currency->user_id === $request->user()->id, 404);
    }
}
