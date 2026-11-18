<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Carbon\CarbonInterface;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the accounts assigned to the user.
     */
    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }

    /**
     * Get the currencies created from the user.
     * @return HasMany
     */
    public function currencies(): HasMany
    {
        return $this->hasMany(Currency::class);
    }

    /**
     * Get the categories created by the user.
     */
    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    /**
     * Get the incomes received into the user's accounts.
     */
    public function incomes(): HasManyThrough
    {
        return $this->hasManyThrough(Income::class, Account::class)->withTrashedParents();
    }

    /**
     * Get the expenses paid from the user's accounts.
     */
    public function expenses(): HasManyThrough
    {
        return $this->hasManyThrough(Expense::class, Account::class)->withTrashedParents();
    }

    /**
     * Creates the starter categories (expenses and incomes) for a newly registered user.
     */
    public function createStarterCategories(): void
    {
        $this->categories()->createMany([
            ['name' => 'Groceries', 'description' => 'Supermarket and food shopping'],
            ['name' => 'Rent', 'description' => 'Rent or mortgage payments'],
            ['name' => 'Transport', 'description' => 'Public transport, fuel, taxis and parking'],
            ['name' => 'Eating out', 'description' => 'Restaurants, cafés and food delivery'],
            ['name' => 'Health', 'description' => 'Pharmacy, doctors and health insurance'],
            ['name' => 'Subscriptions', 'description' => 'Streaming, apps and memberships'],
            ['name' => 'Shopping', 'description' => 'Clothes, home goods and other purchases'],
            ['name' => 'Entertainment', 'description' => 'Leisure, events, hobbies and travel'],
            ['name' => 'Salary', 'description' => 'Regular pay from work'],
            ['name' => 'Freelance', 'description' => 'Income from side jobs or clients'],
            ['name' => 'Other', 'description' => 'Anything that does not fit another category'],
        ]);
    }

    /**
     * Totals the incomes and expenses of each of the last months, oldest first.
     * Months without movements are included with zero totals.
     *
     * @return Collection<int, array{label: string, name: string, income: float, expenses: float}>
     */
    public function monthlyFlow(int $months = 6): Collection
    {
        $firstMonth = now()->startOfMonth()->subMonths($months - 1);
        $range = [$firstMonth, now()->endOfMonth()];

        $incomes = $this->incomes()->whereBetween('incomes.date', $range)->get(['incomes.date', 'incomes.amount'])
            ->groupBy(fn (Income $income): string => $income->date->format('Y-m'));

        $expenses = $this->expenses()->whereBetween('expenses.date', $range)->get(['expenses.date', 'expenses.amount'])
            ->groupBy(fn (Expense $expense): string => $expense->date->format('Y-m'));

        return collect(range(0, $months - 1))->map(function (int $offset) use ($firstMonth, $incomes, $expenses): array {
            $month = $firstMonth->copy()->addMonths($offset);
            $key = $month->format('Y-m');

            return [
                'label' => $month->format('M'),
                'name' => $month->format('F Y'),
                'income' => (float) $incomes->get($key, collect())->sum('amount'),
                'expenses' => (float) $expenses->get($key, collect())->sum('amount'),
            ];
        });
    }

    /**
     * Totals the expenses of the given month (the current one by default) by category, biggest first.
     *
     * @return Collection<int, array{name: string, amount: float}>
     */
    public function monthSpendingByCategory(?CarbonInterface $month = null): Collection
    {
        $month ??= now();

        return $this->expenses()
            ->with('category')
            ->whereBetween('expenses.date', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])
            ->get()
            ->groupBy('category_id')
            ->map(fn (Collection $expenses): array => [
                'name' => $expenses->first()->category->name,
                'amount' => (float) $expenses->sum('amount'),
            ])
            ->sortByDesc('amount')
            ->values();
    }
}
