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

/**
 * Handles creating, correcting and deleting transfers between the user's accounts.
 */
class TransferController extends Controller
{
    /**
     * Shows the transfer form, or an explanation when there are fewer than two accounts.
     */
    public function create(Request $request): View
    {
        Gate::authorize('create', Transfer::class);

        $accounts = $request->user()->accounts()->with('currency:id,symbol')->orderBy('name')->get(['id', 'name', 'balance_in_cents', 'currency_id']);

        return view('userzone.transfers.create', [
            'accounts' => $accounts,
        ]);
    }

    /**
     * Creates the transfer and moves the amount between the two balances.
     */
    public function store(StoreTransferRequest $request): RedirectResponse
    {
        Gate::authorize('create', Transfer::class);

        Transfer::createAndSyncBalances($request->validated());

        return redirect()->route('accounts.index')->with('status', 'transfer-created');
    }

    /**
     * Shows the edit form; the transfer's own accounts are offered even if deactivated.
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

    /**
     * Updates the transfer and both balances, checking them once the change is applied.
     */
    public function update(UpdateTransferRequest $request, Transfer $transfer): RedirectResponse
    {
        Gate::authorize('update', $transfer);

        $transfer->updateAndSyncBalances($request->validated());

        return redirect()->route('accounts.index')->with('status', 'transfer-updated');
    }

    /**
     * Deletes the transfer unless the destination already spent the money.
     */
    public function destroy(Transfer $transfer): RedirectResponse
    {
        Gate::authorize('delete', $transfer);

        if (! $transfer->canBeDeleted()) {
            return redirect()->route('accounts.index')
                ->withErrors(['transfer' => $transfer->toAccount->name.' would end up with a negative balance, so this transfer can’t be deleted.']);
        }

        $transfer->deleteAndRestoreBalances();

        return redirect()->route('accounts.index')->with('status', 'transfer-deleted');
    }
}
