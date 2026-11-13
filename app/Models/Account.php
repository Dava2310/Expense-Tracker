<?php

namespace App\Models;

use Database\Factories\AccountFactory;
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
