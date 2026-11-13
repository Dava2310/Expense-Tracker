<?php

namespace App\Http\Controllers\Userzone;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAccountRequest;
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

        // Getting to the dashboard after a successful creation
        return redirect()->route('dashboard')->with('status', 'account-created');
    }
}
