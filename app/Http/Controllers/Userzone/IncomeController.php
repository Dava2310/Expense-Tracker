<?php

namespace App\Http\Controllers\Userzone;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Income;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class IncomeController extends Controller
{
    /**
     * The periods the incomes can be limited to.
     *
     * @var array<string, string>
     */
    private const PERIODS = [
        'all' => 'All time',
        '3m' => 'Last 3 months',
        'month' => 'This month',
        '6m' => 'Last 6 months',
        'year' => 'This year',
        'custom' => 'Custom range',
    ];

    private const DEFAULT_PERIOD = 'all';

    /***
     * Lists the incomes received into the logged-in user's accounts, grouped by month
     * and optionally filtered by account, category and period
     *
     * @param Request $request
     * @return View
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        // Unknown ids (or accounts that belong to somebody else) are ignored
        $accountId = $user->accounts()->withTrashed()->whereKey($request->integer('account'))->value('id');
        $categoryId = Category::whereKey($request->integer('category'))->value('id');

        $requestedPeriod = $request->query('period');
        $period = is_string($requestedPeriod) && isset(self::PERIODS[$requestedPeriod])
            ? $requestedPeriod
            : self::DEFAULT_PERIOD;

        $from = $this->parseDate($request->query('from'));
        $to = $this->parseDate($request->query('to'));

        if ($from && $to && $from->gt($to)) {
            [$from, $to] = [$to, $from];
        }

        [$start, $end] = match ($period) {
            '3m' => [now()->subMonths(2)->startOfMonth(), null],
            'month' => [now()->startOfMonth(), null],
            '6m' => [now()->subMonths(5)->startOfMonth(), null],
            'year' => [now()->startOfYear(), null],
            'custom' => [$from?->startOfDay(), $to?->endOfDay()],
            default => [null, null],
        };

        $filteredIncomes = fn () => $user->incomes()
            ->when($accountId, fn ($query) => $query->where('incomes.account_id', $accountId))
            ->when($categoryId, fn ($query) => $query->where('incomes.category_id', $categoryId));

        $incomes = $filteredIncomes()
            ->with(['account', 'category'])
            ->when($start, fn ($query) => $query->where('incomes.date', '>=', $start))
            ->when($end, fn ($query) => $query->where('incomes.date', '<=', $end))
            ->orderByDesc('incomes.date')
            ->orderByDesc('incomes.id')
            ->get();

        $groups = $incomes
            ->groupBy(fn (Income $income): string => $income->date->format('Y-m'))
            ->map(fn ($items): array => [
                'title' => $items->first()->date->format('F Y'),
                'count' => $items->count(),
                'total' => $items->sum(fn (Income $income): float => (float) $income->amount),
                'incomes' => $items,
            ])
            ->values();

        $accounts = $user->accounts()->withTrashed()->orderBy('name')->get(['id', 'name', 'deleted_at']);
        $categories = Category::orderBy('name')->get(['id', 'name']);

        $scope = collect([
            $accountId ? 'into '.$accounts->firstWhere('id', $accountId)->name : null,
            $categoryId ? 'from '.$categories->firstWhere('id', $categoryId)->name : null,
        ])->filter()->whenEmpty(fn ($parts) => $parts->push('across all accounts'))->implode(' ');

        return view('userzone.incomes.index', [
            'groups' => $groups,
            'total' => $incomes->sum(fn (Income $income): float => (float) $income->amount),
            'periodPhrase' => $this->describePeriod($period, $from, $to),
            'scope' => $scope,
            'accounts' => $accounts,
            'categories' => $categories,
            'accountId' => $accountId,
            'categoryId' => $categoryId,
            'periods' => self::PERIODS,
            'period' => $period,
            'from' => $from?->format('Y-m-d'),
            'to' => $to?->format('Y-m-d'),
            'activeFilters' => collect([$accountId, $categoryId, $period !== self::DEFAULT_PERIOD])->filter()->count(),
            'outsideCount' => $filteredIncomes()->count() - $incomes->count(),
        ]);
    }

    /**
     * Reads a Y-m-d date from the query string, ignoring anything that is not a real date.
     */
    private function parseDate(mixed $value): ?CarbonImmutable
    {
        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        try {
            $date = CarbonImmutable::createFromFormat('!Y-m-d', $value);
        } catch (Throwable) {
            return null;
        }

        return $date && $date->format('Y-m-d') === $value ? $date : null;
    }

    /**
     * Describes the selected period for the page subtitle ("received {phrase} ...").
     */
    private function describePeriod(string $period, ?CarbonInterface $from, ?CarbonInterface $to): string
    {
        return match ($period) {
            '3m' => 'in the last 3 months',
            'month' => 'this month',
            '6m' => 'in the last 6 months',
            'year' => 'this year',
            'custom' => match (true) {
                $from !== null && $to !== null => 'between '.$from->format('M j, Y').' and '.$to->format('M j, Y'),
                $from !== null => 'since '.$from->format('M j, Y'),
                $to !== null => 'until '.$to->format('M j, Y'),
                default => 'in total',
            },
            default => 'in total',
        };
    }
}
