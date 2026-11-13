<?php

namespace App\Models;

use Database\Factories\IncomeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'date' => 'date',
        ];
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
