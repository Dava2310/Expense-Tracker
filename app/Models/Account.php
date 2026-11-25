<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\AccountFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Number;

class Account extends Model
{
    /** @use HasFactory<AccountFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'bank',
        'details',
        'currency_id',
        'balance',
        'min_balance',
    ];

    /**
     * New accounts start with an empty balance, no details and no minimum balance.
     *
     * @var array<string, int|string>
     */
    protected $attributes = [
        'balance_in_cents' => 0,
        'details' => '',
        'min_balance_in_cents' => 0,
    ];

    /**
     * The balance is read and written as a decimal (12.34) but stored as whole cents (1234).
     */
    protected function balance(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes): float => $this->toDecimal($attributes['balance_in_cents']),
            set: fn (string|float $decimal): array => ['balance_in_cents' => $this->toCents($decimal)],
        );
    }

    /**
     * The minimum balance is read and written as a decimal but stored as whole cents.
     * It is optional; an empty value means no minimum (0).
     */
    protected function minBalance(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes): float => $this->toDecimal($attributes['min_balance_in_cents']),
            set: fn (string|float|null $decimal): array => ['min_balance_in_cents' => $this->toCents($decimal ?? 0)],
        );
    }

    /**
     * Details are optional; an empty value is stored as an empty string.
     */
    protected function details(): Attribute
    {
        return Attribute::set(fn (?string $value): string => $value ?? '');
    }

    /**
     * Formats an amount with the symbol of the currency this account is held in.
     */
    public function formatMoney(float $amount, int $precision = 2): string
    {
        return $this->currency->symbol.Number::format($amount, $precision, locale: 'en');
    }

    /**
     * Adds the given decimal amount to the balance.
     */
    public function deposit(string|float $amount): void
    {
        $this->increment('balance_in_cents', $this->toCents($amount));
    }

    /**
     * Takes the given decimal amount out of the balance.
     */
    public function withdraw(string|float $amount): void
    {
        $this->decrement('balance_in_cents', $this->toCents($amount));
    }

    /**
     * Determines whether the balance, plus an amount that is about to come back to it, is enough to pay the given amount.
     */
    public function hasEnoughBalanceFor(string|float $amount, string|float $returning = 0): bool
    {
        return $this->balance_in_cents + $this->toCents($returning) >= $this->toCents($amount);
    }

    /**
     * Determines whether the balance has dropped under the account's minimum.
     */
    public function isBelowMinimum(): bool
    {
        return $this->balance_in_cents < $this->min_balance_in_cents;
    }

    /**
     * Gets how far the balance is from the minimum balance as a decimal, whether above or below it.
     * Use isBelowMinimum() to know which side the balance is on.
     */
    public function distanceToMinimum(): float
    {
        return $this->toDecimal(abs($this->balance_in_cents - $this->min_balance_in_cents));
    }

    /**
     * Gets the latest incomes and expenses of this account, newest first.
     *
     * @return Collection<int, array{type: string, concept: string, date: CarbonInterface, category: string, amount: float}>
     */
    public function recentActivity(int $limit = 20): Collection
    {
        $entryColumns = ['id', 'concept', 'date', 'amount_in_cents', 'category_id'];

        $incomes = $this->incomes()->select($entryColumns)->with('category:id,name')->latest('date')->latest('id')->limit($limit)->get()
            ->map(fn (Income $income): array => $this->activityEntry('income', $income));

        $expenses = $this->expenses()->select($entryColumns)->with('category:id,name')->latest('date')->latest('id')->limit($limit)->get()
            ->map(fn (Expense $expense): array => $this->activityEntry('expense', $expense));

        return $incomes->concat($expenses)
            ->sortBy([['date', 'desc'], ['id', 'desc']])
            ->take($limit)
            ->values();
    }

    /**
     * Sums the incomes or expenses of this account received in the given month.
     *
     * @param  'incomes'|'expenses'  $relation
     */
    public function monthTotal(string $relation, ?CarbonInterface $month = null): float
    {
        $month ??= now();

        return $this->toDecimal((int) $this->{$relation}()
            ->whereBetween('date', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])
            ->sum('amount_in_cents'));
    }

    /**
     * Totals the incomes and expenses of this account in each of the last months, oldest first.
     * Months without movements are included with zero totals.
     *
     * @return Collection<int, array{label: string, name: string, income: float, expenses: float, net: float, surplus: bool}>
     */
    public function monthlyFlow(int $months = 6): Collection
    {
        $firstMonth = now()->startOfMonth()->subMonths($months - 1);
        $monthList = collect(range(0, $months - 1))->map(fn (int $offset): CarbonInterface => $firstMonth->copy()->addMonths($offset));

        $incomes = $this->monthlyTotalsInCents($this->incomes(), $monthList);
        $expenses = $this->monthlyTotalsInCents($this->expenses(), $monthList);

        return $monthList->map(function (CarbonInterface $month, int $index) use ($incomes, $expenses): array {
            return [
                'label' => $month->format('M'),
                'name' => $month->format('F Y'),
                'income' => $this->toDecimal($incomes[$index]),
                'expenses' => $this->toDecimal($expenses[$index]),
                'net' => $this->toDecimal(abs($incomes[$index] - $expenses[$index])),
                'surplus' => $incomes[$index] >= $expenses[$index],
            ];
        });
    }

    /**
     * Totals the expenses of this account in the given month (the current one by default) by category, biggest first.
     *
     * @return Collection<int, array{name: string, amount: float}>
     */
    public function monthSpendingByCategory(?CarbonInterface $month = null): Collection
    {
        $month ??= now();

        return $this->expenses()
            ->join('categories', 'categories.id', '=', 'expenses.category_id')
            ->whereBetween('expenses.date', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])
            ->groupBy('expenses.category_id', 'categories.name')
            ->selectRaw('categories.name, SUM(expenses.amount_in_cents) as total')
            ->orderByDesc('total')
            ->orderBy('categories.name')
            ->toBase()
            ->get()
            ->map(fn (object $row): array => [
                'name' => $row->name,
                'amount' => $this->toDecimal((int) $row->total),
            ]);
    }

    /**
     * Gets the information of the user that owns this account.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Gets the currency this bank account is held in.
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * Gets all expenses associated to this bank account.
     */
    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    /**
     * Gets all incomes associated to this bank account.
     */
    public function incomes(): HasMany
    {
        return $this->hasMany(Income::class);
    }

    /**
     * Totals the given incomes or expenses of each month in cents with a single query, in the order of the months.
     *
     * @param  HasMany<Income|Expense, $this>  $entries
     * @param  Collection<int, CarbonInterface>  $months
     * @return list<int>
     */
    private function monthlyTotalsInCents(HasMany $entries, Collection $months): array
    {
        foreach ($months as $index => $month) {
            $entries->selectRaw(
                'SUM(CASE WHEN date BETWEEN ? AND ? THEN amount_in_cents ELSE 0 END) as month_'.$index,
                [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()],
            );
        }

        return array_map('intval', array_values((array) $entries->toBase()->first()));
    }

    /**
     * Converts a decimal amount (12.34) into whole cents (1234). The only place that knows how many cents make a unit.
     */
    private function toCents(string|float $decimal): int
    {
        return (int) round((float) $decimal * 100);
    }

    /**
     * Converts whole cents (1234) into a decimal amount (12.34).
     */
    private function toDecimal(int $cents): float
    {
        return $cents / 100;
    }

    /**
     * Builds a display-ready activity entry from an income or an expense.
     *
     * @return array{id: int, type: string, concept: string, date: CarbonInterface, category: string, amount: float}
     */
    private function activityEntry(string $type, Income|Expense $entry): array
    {
        return [
            'id' => $entry->id,
            'type' => $type,
            'concept' => $entry->concept,
            'date' => $entry->date,
            'category' => $entry->category->name,
            'amount' => $entry->amount,
        ];
    }
}
