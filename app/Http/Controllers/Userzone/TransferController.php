<?php

namespace App\Http\Controllers\Userzone;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTransferRequest;
use App\Http\Requests\UpdateTransferRequest;
use App\Models\Transfer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class TransferController extends Controller
{
    /***
     * Renders the form to move money between two of the user's accounts
     *
     * @param Request $request
     * @return View
     */
    public function create(Request $request): View
    {
        Gate::authorize('create', Transfer::class);

        $accounts = $request->user()->accounts()->with('currency:id,symbol')->orderBy('name')->get(['id', 'name', 'balance_in_cents', 'currency_id']);

        return view('userzone.transfers.create', [
            'accounts' => $accounts,
        ]);
    }

    /***
     * Handles the creation of the transfer and moves its amount between the two balances
     *
     * @param StoreTransferRequest $request
     * @return RedirectResponse
     */
    public function store(StoreTransferRequest $request): RedirectResponse
    {
        Gate::authorize('create', Transfer::class);

        Transfer::createAndSyncBalances($request->validated());

        return redirect()->route('accounts.index')->with('status', 'transfer-created');
    }

    /***
     * Shows the form to edit all the details of one of the user's transfers
     *
     * @param Request $request
     * @param Transfer $transfer
     * @return View
     */
    public function edit(Request $request, Transfer $transfer): View
    {
        Gate::authorize('update', $transfer);

        // The active accounts, plus the accounts the transfer already uses even if they were deactivated
        $accounts = $request->user()->accounts()->withTrashed()
            ->where(fn ($query) => $query->whereNull('deleted_at')->orWhereIn('id', [$transfer->from_account_id, $transfer->to_account_id]))
            ->with('currency:id,symbol')
            ->orderBy('name')
            ->get(['id', 'name', 'balance_in_cents', 'currency_id']);

        return view('userzone.transfers.edit', [
            'transfer' => $transfer,
            'accounts' => $accounts,
        ]);
    }

    /***
     * Handles the update of a transfer and keeps the balances of the accounts right
     *
     * @param UpdateTransferRequest $request
     * @param Transfer $transfer
     * @return RedirectResponse
     */
    public function update(UpdateTransferRequest $request, Transfer $transfer): RedirectResponse
    {
        Gate::authorize('update', $transfer);

        $transfer->updateAndSyncBalances($request->validated());

        return redirect()->route('accounts.index')->with('status', 'transfer-updated');
    }
}
