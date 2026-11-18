<?php

namespace App\Http\Controllers\Userzone;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $accounts = $user->accounts()->with('currency')->orderBy('name')->get();

        $monthlyFlow = $user->monthlyFlow();
        $highestAmount = $monthlyFlow->flatMap(fn (array $month): array => [$month['income'], $month['expenses']])->max();

        $monthSpending = $user->monthSpendingByCategory();

        return view('userzone.dashboard', [
            'accounts' => $accounts,
            'inactiveAccounts' => $user->accounts()->onlyTrashed()->with('currency')->orderBy('name')->get(),
            'monthlyFlow' => $monthlyFlow,
            'chartMax' => $highestAmount > 0 ? $this->roundUpToScale($highestAmount) : 0,
            'categorySpending' => $monthSpending->take(7),
            'categoryTotal' => $monthSpending->sum('amount'),
            'currentMonth' => now()->format('F'),
        ]);
    }

    /**
     * Rounds an amount up to its leading digit (3,650 becomes 4,000) so the chart scale reads well.
     */
    private function roundUpToScale(float $amount): float
    {
        $magnitude = 10 ** floor(log10($amount));

        return ceil($amount / $magnitude) * $magnitude;
    }
}
