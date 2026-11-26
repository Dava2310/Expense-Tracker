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

/**
 * A bank account, cash or card of a user, with its balance in cents and its history.
 */
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
     * New accounts start with an empty balance and no minimum balance.
     *
     * @var array<string, int>
     */
    protected $attributes = [
        'balance_in_cents' => 0,
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
     * Minimum balance as a decimal, stored as whole cents; empty means no minimum.
     */
    protected function minBalance(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes): float => $this->toDecimal($attributes['min_balance_in_cents']),
            set: fn (string|float|null $decimal): array => ['min_balance_in_cents' => $this->toCents($decimal ?? 0)],
        );
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
     * Gets the transfers that take money out of this bank account.
     */
    public function outgoingTransfers(): HasMany
    {
        return $this->hasMany(Transfer::class, 'from_account_id');
    }

    /**
     * Gets the transfers that bring money into this bank account.
     */
    public function incomingTransfers(): HasMany
    {
        return $this->hasMany(Transfer::class, 'to_account_id');
    }

    /**
     * Formats an amount with the symbol of the currency this account is held in.
     *
     * @param  float  $amount  The decimal amount to format
     * @param  int  $precision  How many decimal places to show
     */
    public function formatMoney(float $amount, int $precision = 2): string
    {
        return $this->currency->symbol.Number::format($amount, $precision, locale: 'en');
    }

    /**
     * Adds the given decimal amount to the balance.
     *
     * @param  string|float  $amount  The decimal amount to add
     */
    public function deposit(string|float $amount): void
    {
        $this->increment('balance_in_cents', $this->toCents($amount));
    }

    /**
     * Takes the given decimal amount out of the balance.
     *
     * @param  string|float  $amount  The decimal amount to take out
     */
    public function withdraw(string|float $amount): void
    {
        $this->decrement('balance_in_cents', $this->toCents($amount));
    }

    /**
     * Whether the balance, plus money about to come back, is enough to take out the given amount.
     *
     * @param  string|float  $amount  The decimal amount to take out
     * @param  string|float  $returning  Money about to return to the account
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
     * How far the balance is from the minimum, always positive.
     */
    public function distanceToMinimum(): float
    {
        return $this->toDecimal(abs($this->balance_in_cents - $this->min_balance_in_cents));
    }

    /**
     * Gets the latest incomes, expenses and transfers of this account, newest first.
     *
     * @param  int  $limit  The maximum number of entries to return
     * @return Collection<int, array{type: string, concept: string, date: CarbonInterface, category: string, amount: float}>
     */
    public function recentActivity(int $limit = 20): Collection
    {
        $entryColumns = ['id', 'concept', 'date', 'amount_in_cents', 'category_id'];

        $incomes = $this->incomes()->select($entryColumns)->with('category:id,name')->latest('date')->latest('id')->limit($limit)->get()
            ->map(fn (Income $income): array => $this->activityEntry('income', $income));

        $expenses = $this->expenses()->select($entryColumns)->with('category:id,name')->latest('date')->latest('id')->limit($limit)->get()
            ->map(fn (Expense $expense): array => $this->activityEntry('expense', $expense));

        $outgoing = $this->outgoingTransfers()->select(['id', 'to_account_id', 'date', 'amount_in_cents', 'note'])->with('toAccount:id,name')->latest('date')->latest('id')->limit($limit)->get()
            ->map(fn (Transfer $transfer): array => $this->transferEntry('transfer-out', 'Transfer to '.$transfer->toAccount->name, $transfer));

        $incoming = $this->incomingTransfers()->select(['id', 'from_account_id', 'date', 'amount_in_cents', 'note'])->with('fromAccount:id,name')->latest('date')->latest('id')->limit($limit)->get()
            ->map(fn (Transfer $transfer): array => $this->transferEntry('transfer-in', 'Transfer from '.$transfer->fromAccount->name, $transfer));

        return $incomes->concat($expenses)->concat($outgoing)->concat($incoming)
            ->sortBy([['date', 'desc'], ['id', 'desc']])
            ->take($limit)
            ->values();
    }

    /**
     * Sums the incomes or expenses of this account received in the given month.
     *
     * @param  'incomes'|'expenses'  $relation  Which entries to sum
     * @param  CarbonInterface|null  $month  Any date within the month; the current month if empty
     */
    public function monthTotal(string $relation, ?CarbonInterface $month = null): float
    {
        $month ??= now();

        return $this->toDecimal((int) $this->{$relation}()
            ->whereBetween('date', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])
            ->sum('amount_in_cents'));
    }

    /**
     * Income and expenses of each of the last months, oldest first, empty months included.
     *
     * @param  int  $months  How many months to include, counting back from the current one
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
     * Expenses of a month by category, biggest first.
     *
     * @param  CarbonInterface|null  $month  Any date within the month; the current month if empty
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
     * Shapes an income or expense as an activity entry.
     *
     * @param  string  $type  The entry label, 'income' or 'expense'
     * @param  Income|Expense  $entry  The income or expense to shape
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

    /**
     * Shapes a transfer as an activity entry; the note replaces the category.
     *
     * @param  string  $type  The entry label, 'transfer-in' or 'transfer-out'
     * @param  string  $concept  The text shown as the entry's title
     * @param  Transfer  $transfer  The transfer to shape
     * @return array{id: int, type: string, concept: string, date: CarbonInterface, category: string, amount: float}
     */
    private function transferEntry(string $type, string $concept, Transfer $transfer): array
    {
        return [
            'id' => $transfer->id,
            'type' => $type,
            'concept' => $concept,
            'date' => $transfer->date,
            'category' => $transfer->note ?? 'Transfer',
            'amount' => $transfer->amount,
        ];
    }

    /**
     * Totals incomes or expenses per month, in cents, with a single query.
     *
     * @param  HasMany<Income|Expense, $this>  $entries  The incomes or expenses of this account
     * @param  Collection<int, CarbonInterface>  $months  The months to total, oldest first
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
     * Converts a decimal amount (12.34) into whole cents (1234).
     *
     * @param  string|float  $decimal  The decimal amount, such as 12.34
     */
    private function toCents(string|float $decimal): int
    {
        return (int) round((float) $decimal * 100);
    }

    /**
     * Converts whole cents (1234) into a decimal amount (12.34).
     *
     * @param  int  $cents  The amount in whole cents, such as 1234
     */
    private function toDecimal(int $cents): float
    {
        return $cents / 100;
    }
}
