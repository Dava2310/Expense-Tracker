<?php

namespace App\Models;

use Database\Factories\CurrencyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A currency a user holds accounts in.
 */
class Currency extends Model
{
    /** @use HasFactory<CurrencyFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'symbol',
    ];

    /**
     * Gets the user that owns this currency.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Gets the accounts held in this currency, active ones unless withTrashed() is used.
     *
     * @return HasMany<Account, $this>
     */
    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }

    /**
     * Gets the total balance of the accounts in this currency, inactive ones included.
     */
    public function totalBalance(): float
    {
        return (int) $this->accounts()->withTrashed()->sum('balance_in_cents') / 100;
    }

    /**
     * Whether no account, active or inactive, uses this currency.
     */
    public function canBeDeleted(): bool
    {
        return ! $this->accounts()->withTrashed()->exists();
    }
}
