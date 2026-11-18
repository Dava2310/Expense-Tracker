<?php

namespace App\Models;

use Database\Factories\CurrencyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    /***
     * Gets the user that owns this currency.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /***
     * Gets the accounts held in this currency. Inactive (soft-deleted) accounts are
     * left out unless the query uses withTrashed().
     *
     * @return HasMany<Account, $this>
     */
    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }

    /***
     * Determines whether the currency can be deleted, which is only when no account
     * (active or inactive) uses it.
     *
     * @return bool
     */
    public function canBeDeleted(): bool
    {
        return ! $this->accounts()->withTrashed()->exists();
    }
}
