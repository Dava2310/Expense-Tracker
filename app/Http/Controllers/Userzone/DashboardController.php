<?php

namespace App\Http\Controllers\Userzone;

use App\Http\Controllers\Controller;
use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): View
    {
        $accounts = $request->user()->accounts()->orderBy('name')->get();

        return view('userzone.dashboard', [
            'accounts' => $accounts,
            'totalBalance' => $accounts->sum(fn (Account $account): float => (float) $account->balance),
        ]);
    }
}
