<?php

namespace App\Models;

use Database\Factories\ExpenseFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class Expense extends Model
{
    /** @use HasFactory<ExpenseFactory> */
    use HasFactory;

    protected $fillable = [
        'amount',
        'concept',
        'date',
        'account_id',
        'category_id',
    ];

    /**
     * The amount is read and written as a decimal (12.34) but stored as whole cents (1234).
     */
    protected function amount(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes): float => $attributes['amount_in_cents'] / 100,
            set: fn (string|float $decimal): array => ['amount_in_cents' => (int) round((float) $decimal * 100)],
        );
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    /**
     * Determines whether the given account can pay the new amount of this expense. The money of this expense goes
     * back to its account first, so it counts as available when the account stays the same.
     */
    public function canBeChangedTo(Account $account, string|float $amount): bool
    {
        $returning = $account->is($this->account) ? $this->amount : 0;

        return $account->hasEnoughBalanceFor($amount, $returning);
    }

    /**
     * Saves the changes and keeps the balances right: the old amount goes back to the old account
     * and the new amount is taken from the new one, which may be the same account.
     * The expense and both balance changes succeed or fail together.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function updateAndSyncBalance(array $attributes): void
    {
        DB::transaction(function () use ($attributes): void {
            $this->account->deposit($this->amount);

            $this->update($attributes);

            $this->load('account');
            $this->account->withdraw($this->amount);
        });
    }

    /**
     * Deletes the expense and gives its amount back to the account, both or neither.
     */
    public function deleteAndRestoreBalance(): void
    {
        DB::transaction(function (): void {
            $this->account->deposit($this->amount);

            $this->delete();
        });
    }

    /**
     * Gets the associated account for the expense.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class)->withTrashed();
    }

    /**
     * Gets the associated category for the expense.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
