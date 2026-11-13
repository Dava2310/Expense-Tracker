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

class Account extends Model
{
    /** @use HasFactory<AccountFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'bank',
        'details',
        'balance',
        'min_balance',
    ];

    /**
     * New accounts start with an empty balance, no details and no minimum balance.
     *
     * @var array<string, int|string>
     */
    protected $attributes = [
        'balance' => 0,
        'details' => '',
        'min_balance' => 0,
    ];

    /**
     * The minimum balance is optional; an empty value means no minimum (0).
     */
    protected function minBalance(): Attribute
    {
        return Attribute::set(fn (mixed $value): mixed => $value ?? 0);
    }

    /**
     * Details are optional; an empty value is stored as an empty string.
     */
    protected function details(): Attribute
    {
        return Attribute::set(fn (?string $value): string => $value ?? '');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'balance' => 'decimal:2',
            'min_balance' => 'decimal:2',
        ];
    }

    /**
     * Determines whether the balance has dropped under the account's minimum.
     */
    public function isBelowMinimum(): bool
    {
        return round((float) $this->balance - (float) $this->min_balance, 2) < 0;
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

        return (float) $this->{$relation}()
            ->whereBetween('date', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])
            ->sum('amount');
    }

    /**
     * Gets the information of the user that owns this account.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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
            'amount' => (float) $entry->amount,
        ];
    }
}
