<?php

namespace App\Models;

use Database\Factories\TransferFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Money moved between two accounts of the same user; it is neither an income nor an expense.
 */
class Transfer extends Model
{
    /** @use HasFactory<TransferFactory> */
    use HasFactory;

    protected $fillable = [
        'amount',
        'date',
        'note',
        'from_account_id',
        'to_account_id',
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
     * Creates the transfer and moves its amount from one balance to the other, both or neither.
     *
     * @param  array<string, mixed>  $attributes
     */
    public static function createAndSyncBalances(array $attributes): self
    {
        return DB::transaction(function () use ($attributes): self {
            $transfer = self::create($attributes);

            $transfer->fromAccount->withdraw($transfer->amount);
            $transfer->toAccount->deposit($transfer->amount);

            return $transfer;
        });
    }

    /**
     * Saves the change and the balances in one transaction; rolls back if an account ends below zero.
     *
     * @param  array<string, mixed>  $attributes
     *
     * @throws ValidationException
     */
    public function updateAndSyncBalances(array $attributes): void
    {
        DB::transaction(function () use ($attributes): void {
            $touchedAccountIds = [$this->from_account_id, $this->to_account_id];

            $this->fromAccount->deposit($this->amount);
            $this->toAccount->withdraw($this->amount);

            $this->update($attributes);

            $this->load(['fromAccount', 'toAccount']);
            $this->fromAccount->withdraw($this->amount);
            $this->toAccount->deposit($this->amount);

            $accountBelowZero = Account::withTrashed()
                ->whereIn('id', [...$touchedAccountIds, $this->from_account_id, $this->to_account_id])
                ->where('balance_in_cents', '<', 0)
                ->first();

            if ($accountBelowZero) {
                throw ValidationException::withMessages([
                    'amount' => $accountBelowZero->name.' would end up with a negative balance.',
                ]);
            }
        });
    }

    /**
     * Determines whether the destination can give the money back, without ending up below zero.
     */
    public function canBeDeleted(): bool
    {
        return $this->toAccount->hasEnoughBalanceFor($this->amount);
    }

    /**
     * Deletes the transfer and puts the amount back from the destination to the source, both or neither.
     */
    public function deleteAndRestoreBalances(): void
    {
        DB::transaction(function (): void {
            $this->fromAccount->deposit($this->amount);
            $this->toAccount->withdraw($this->amount);

            $this->delete();
        });
    }

    /**
     * Gets the account the money leaves.
     */
    public function fromAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'from_account_id')->withTrashed();
    }

    /**
     * Gets the account the money arrives in.
     */
    public function toAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'to_account_id')->withTrashed();
    }
}
