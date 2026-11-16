<?php

namespace App\Models;

use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
    ];

    /**
     * Returns the user the category belongs to.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Whether any income or expense uses this category, which stops it from being deleted.
     */
    public function isInUse(): bool
    {
        return $this->incomes()->exists() || $this->expenses()->exists();
    }

    /**
     * Returns all expenses associated with this category.
     */
    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    /**
     * Returns all incomes associated with this category.
     */
    public function incomes(): HasMany
    {
        return $this->hasMany(Income::class);
    }
}
