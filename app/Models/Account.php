<?php

namespace App\Models;

use Database\Factories\AccountFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    /** @use HasFactory<AccountFactory> */
    use HasFactory;

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
}
