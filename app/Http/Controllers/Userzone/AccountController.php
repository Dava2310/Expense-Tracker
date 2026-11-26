<?php

namespace App\Http\Controllers\Userzone;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAccountRequest;
use App\Http\Requests\UpdateAccountRequest;
use App\Models\Account;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Handles listing, creating, showing, editing and deactivating the user's accounts.
 */
class AccountController extends Controller
{
    /**
     * Lists the user's active accounts.
     */
    public function index(Request $request): View
    {
        return view('userzone.accounts.index', [
            'accounts' => $request->user()->accounts()
                ->select('id', 'name', 'bank', 'details', 'balance_in_cents', 'min_balance_in_cents', 'currency_id')
                ->with('currency:id,symbol')
                ->latest()
                ->get(),
        ]);
    }

    /**
     * Renders the form to create a new account
     */
    public function create(Request $request): View
    {
        Gate::authorize('create', Account::class);

        return view('userzone.accounts.create', [
            'currencies' => $request->user()->currencies()->orderBy('name')->get(),
        ]);
    }

    /**
     * Creates an account, whose balance starts at 0.
     */
    public function store(StoreAccountRequest $request): RedirectResponse
    {
        Gate::authorize('create', Account::class);

        // Creation of the account
        $request->user()->accounts()->create($request->validated());

        // Getting to the accounts list after a successful creation
        return redirect()->route('accounts.index')->with('status', 'account-created');
    }

    /**
     * Shows one of the user's accounts with its summary and latest activity
     */
    public function show(Account $account): View
    {
        Gate::authorize('view', $account);

        return view('userzone.accounts.show', [
            'account' => $account->load('currency:id,symbol'),
            'activity' => $account->recentActivity(),
            'monthIncome' => $account->monthTotal('incomes'),
            'monthExpenses' => $account->monthTotal('expenses'),
        ]);
    }

    /**
     * Renders the form to edit one of the user's accounts
     */
    public function edit(Account $account): View
    {
        Gate::authorize('update', $account);

        return view('userzone.accounts.edit', ['account' => $account->load('currency')]);
    }

    /**
     * Updates an account; the balance is not editable, only entries and transfers move it.
     */
    public function update(UpdateAccountRequest $request, Account $account): RedirectResponse
    {
        Gate::authorize('update', $account);

        $account->update($request->validated());

        return redirect()->route('accounts.show', $account)->with('status', 'account-updated');
    }

    /**
     * Deactivates an account instead of deleting it, so its history is kept.
     */
    public function destroy(Account $account): RedirectResponse
    {
        Gate::authorize('delete', $account);

        $account->delete();

        return redirect()->route('accounts.index')->with('status', 'account-deactivated');
    }
}
