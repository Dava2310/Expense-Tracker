<?php

use App\Models\Account;
use App\Models\Category;
use App\Models\Expense;
use App\Models\Income;
use App\Models\User;
use Illuminate\Support\Carbon;

test('recent activity merges incomes and expenses, newest first, up to the limit', function () {
    $account = Account::factory()->create();
    $category = Category::factory()->create(['name' => 'Salary']);
    Income::factory()->for($account)->for($category)->create(['concept' => 'Oldest income', 'amount' => 10, 'date' => '2026-09-01']);
    Expense::factory()->for($account)->for($category)->create(['concept' => 'Middle expense', 'amount' => 20, 'date' => '2026-10-01']);
    Income::factory()->for($account)->for($category)->create(['concept' => 'Newest income', 'amount' => 30, 'date' => '2026-11-01']);

    $activity = $account->recentActivity();

    expect($activity->pluck('concept')->all())->toBe(['Newest income', 'Middle expense', 'Oldest income'])
        ->and($activity->pluck('type')->all())->toBe(['income', 'expense', 'income'])
        ->and($activity->first()['category'])->toBe('Salary')
        ->and($activity->first()['amount'])->toBe(30.0)
        ->and($account->recentActivity(2)->pluck('concept')->all())->toBe(['Newest income', 'Middle expense']);
});

test('month totals only add up the entries of the given month', function () {
    $this->travelTo(Carbon::parse('2026-11-13 12:00:00'));
    $account = Account::factory()->create();
    $category = Category::factory()->create();
    Income::factory()->for($account)->for($category)->create(['amount' => 100, 'date' => '2026-11-01']);
    Income::factory()->for($account)->for($category)->create(['amount' => 50.5, 'date' => '2026-11-30']);
    Income::factory()->for($account)->for($category)->create(['amount' => 999, 'date' => '2026-10-31']);
    Expense::factory()->for($account)->for($category)->create(['amount' => 25, 'date' => '2026-11-15']);

    expect($account->monthTotal('incomes'))->toBe(150.5)
        ->and($account->monthTotal('expenses'))->toBe(25.0)
        ->and($account->monthTotal('incomes', Carbon::parse('2026-10-15')))->toBe(999.0);
});

test('a deactivated account is hidden by default but keeps its history', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create();
    $income = Income::factory()->for($account)->create();
    $expense = Expense::factory()->for($account)->create();

    $account->delete();

    expect($user->accounts)->toHaveCount(0)
        ->and($user->accounts()->withTrashed()->count())->toBe(1)
        ->and($income->fresh()->account->is($account))->toBeTrue()
        ->and($income->fresh()->account->trashed())->toBeTrue()
        ->and($expense->fresh()->account->trashed())->toBeTrue()
        ->and($user->incomes)->toHaveCount(1);
});
