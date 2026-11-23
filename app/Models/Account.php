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
            get: fn (mixed $value, array $attributes): float => $attributes['balance_in_cents'] / 100,
            set: fn (string|float $decimal): array => ['balance_in_cents' => (int) round((float) $decimal * 100)],
        );
    }

    /**
     * The minimum balance is read and written as a decimal but stored as whole cents.
     * It is optional; an empty value means no minimum (0).
     */
    protected function minBalance(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes): float => $attributes['min_balance_in_cents'] / 100,
            set: fn (string|float|null $decimal): array => ['min_balance_in_cents' => (int) round((float) ($decimal ?? 0) * 100)],
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
        $this->changeBalanceByCents((int) round((float) $amount * 100));
    }

    /**
     * Takes the given decimal amount out of the balance.
     */
    public function withdraw(string|float $amount): void
    {
        $this->changeBalanceByCents(-(int) round((float) $amount * 100));
    }

    /**
     * Determines whether the balance is enough to pay the given amount.
     */
    public function hasEnoughBalanceFor(string|float $amount): bool
    {
        return $this->balance_in_cents >= (int) round((float) $amount * 100);
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
        return abs($this->balance_in_cents - $this->min_balance_in_cents) / 100;
    }

    /**
     * Gets the latest incomes and expenses of this account, newest first.
     *
     * @return Collection<int, array{type: string, concept: string, date: CarbonInterface, category: string, amount: float}>
     */
    public function recentActivity(int $limit = 20): Collection
    {
        $incomes = $this->incomes()->with('category')->latest('date')->latest('id')->limit($limit)->get()
            ->map(fn (Income $income): array => $this->activityEntry('income', $income));

        $expenses = $this->expenses()->with('category')->latest('date')->latest('id')->limit($limit)->get()
            ->map(fn (Expense $expense): array => $this->activityEntry('expense', $expense));

        return $incomes->concat($expenses)
            ->sortByDesc(fn (array $entry): string => $entry['date']->format('Y-m-d').str_pad((string) $entry['id'], 10, '0', STR_PAD_LEFT))
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

        return $this->centsToFloat((int) $this->{$relation}()
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
        $range = [$firstMonth, now()->endOfMonth()];

        $incomes = $this->incomes()->whereBetween('date', $range)->get(['date', 'amount_in_cents'])
            ->groupBy(fn (Income $income): string => $income->date->format('Y-m'));

        $expenses = $this->expenses()->whereBetween('date', $range)->get(['date', 'amount_in_cents'])
            ->groupBy(fn (Expense $expense): string => $expense->date->format('Y-m'));

        return collect(range(0, $months - 1))->map(function (int $offset) use ($firstMonth, $incomes, $expenses): array {
            $month = $firstMonth->copy()->addMonths($offset);
            $key = $month->format('Y-m');

            $incomeCents = $incomes->get($key, collect())->sum('amount_in_cents');
            $expenseCents = $expenses->get($key, collect())->sum('amount_in_cents');

            return [
                'label' => $month->format('M'),
                'name' => $month->format('F Y'),
                'income' => $this->centsToFloat($incomeCents),
                'expenses' => $this->centsToFloat($expenseCents),
                'net' => $this->centsToFloat(abs($incomeCents - $expenseCents)),
                'surplus' => $incomeCents >= $expenseCents,
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
            ->with('category')
            ->whereBetween('date', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])
            ->get()
            ->groupBy('category_id')
            ->map(fn (Collection $expenses): array => [
                'name' => $expenses->first()->category->name,
                'amount' => $this->centsToFloat($expenses->sum('amount_in_cents')),
            ])
            ->sortByDesc('amount')
            ->values();
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
     * Changes the balance by a number of cents with one database-side update, so it is exact
     * and does not depend on the value loaded earlier. The model is refreshed afterwards.
     */
    private function changeBalanceByCents(int $cents): void
    {
        static::whereKey($this->getKey())->increment('balance_in_cents', $cents);

        $this->refresh();
    }

    /**
     * Converts a total in whole cents into a decimal amount for display.
     */
    private function centsToFloat(int $cents): float
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
