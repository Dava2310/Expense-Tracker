<?php

namespace App\Http\Controllers\Userzone;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTransferRequest;
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
}
