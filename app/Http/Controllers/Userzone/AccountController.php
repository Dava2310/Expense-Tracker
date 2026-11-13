<?php

namespace App\Http\Controllers\Userzone;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAccountRequest;
use App\Http\Requests\UpdateAccountRequest;
use App\Models\Account;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountController extends Controller
{
    /***
     * Lists the accounts that belong to the logged-in user
     *
     * @param Request $request
     * @return View
     */
    public function index(Request $request): View
    {
        return view('userzone.accounts.index', [
            'accounts' => $request->user()->accounts()->latest()->get(),
        ]);
    }

    /***
     * Renders the form to create a new account
     *
     * @return View
     */
    public function create(): View
    {
        return view('userzone.accounts.create');
    }

    /***
     * Handles the creation of the new Account
     *
     * @param StoreAccountRequest $request
     * @return RedirectResponse
     */
    public function store(StoreAccountRequest $request): RedirectResponse
    {
        // Creation of the account
        $request->user()->accounts()->create($request->validated());

        // Getting to the accounts list after a successful creation
        return redirect()->route('accounts.index')->with('status', 'account-created');
    }

    /***
     * Shows one of the user's accounts with its summary and latest activity
     *
     * @param Request $request
     * @param Account $account
     * @return View
     */
    public function show(Request $request, Account $account): View
    {
        $this->ensureOwnedBy($request, $account);

        return view('userzone.accounts.show', [
            'account' => $account,
            'activity' => $account->recentActivity(),
            'monthIncome' => $account->monthTotal('incomes'),
            'monthExpenses' => $account->monthTotal('expenses'),
            'aboveMinimum' => (float) $account->balance - (float) $account->min_balance,
        ]);
    }

    /***
     * Renders the form to edit one of the user's accounts
     *
     * @param Request $request
     * @param Account $account
     * @return View
     */
    public function edit(Request $request, Account $account): View
    {
        $this->ensureOwnedBy($request, $account);

        return view('userzone.accounts.edit', ['account' => $account]);
    }

    /***
     * Handles the update of an account (balance cannot be changed)
     *
     * @param UpdateAccountRequest $request
     * @param Account $account
     * @return RedirectResponse
     */
    public function update(UpdateAccountRequest $request, Account $account): RedirectResponse
    {
        $account->update($request->validated());

        return redirect()->route('accounts.show', $account)->with('status', 'account-updated');
    }

    /***
     * Deactivates one of the user's accounts. Its incomes and expenses are kept.
     *
     * @param Request $request
     * @param Account $account
     * @return RedirectResponse
     */
    public function destroy(Request $request, Account $account): RedirectResponse
    {
        $this->ensureOwnedBy($request, $account);

        $account->delete();

        return redirect()->route('accounts.index')->with('status', 'account-deactivated');
    }

    /**
     * Accounts that belong to somebody else are reported as not found.
     */
    private function ensureOwnedBy(Request $request, Account $account): void
    {
        abort_unless($account->user_id === $request->user()->id, 404);
    }
}
