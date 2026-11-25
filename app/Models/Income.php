<?php

namespace App\Models;

use Database\Factories\IncomeFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class Income extends Model
{
    /** @use HasFactory<IncomeFactory> */
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
     * Determines whether the account of this income can give the old amount back. Only that account can end up
     * short: the new amount also arrives there when the account stays the same, and another account only gains.
     */
    public function canBeChangedTo(Account $account, string|float $amount): bool
    {
        $incoming = $account->is($this->account) ? $amount : 0;

        return $this->account->hasEnoughBalanceFor($this->amount, $incoming);
    }

    /**
     * Saves the changes and keeps the balances right: the old amount is taken out of the old account
     * and the new amount is added to the new one, which may be the same account.
     * The income and both balance changes succeed or fail together.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function updateAndSyncBalance(array $attributes): void
    {
        DB::transaction(function () use ($attributes): void {
            $this->account->withdraw($this->amount);

            $this->update($attributes);

            $this->load('account');
            $this->account->deposit($this->amount);
        });
    }

    /**
     * Determines whether the account can give the money of this income back, without ending up below zero.
     */
    public function canBeDeleted(): bool
    {
        return $this->account->hasEnoughBalanceFor($this->amount);
    }

    /**
     * Deletes the income and takes its amount out of the account, both or neither.
     */
    public function deleteAndRestoreBalance(): void
    {
        DB::transaction(function (): void {
            $this->account->withdraw($this->amount);

            $this->delete();
        });
    }

    /**
     * Gets the associated account for the Income.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class)->withTrashed();
    }

    /**
     * Gets the associated category for the Income.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
