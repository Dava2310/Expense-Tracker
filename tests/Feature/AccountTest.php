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

test('balance and minimum balance are stored as whole cents and read back as decimals', function () {
    $account = Account::factory()->create(['balance' => 12.34, 'min_balance' => '0.10'])->fresh();

    expect($account->balance_in_cents)->toBe(1234)
        ->and($account->balance)->toBe('12.34')
        ->and($account->min_balance_in_cents)->toBe(10)
        ->and($account->min_balance)->toBe('0.10');
});

test('an empty minimum balance is stored as zero cents', function () {
    $account = Account::factory()->create(['min_balance' => null])->fresh();

    expect($account->min_balance_in_cents)->toBe(0);
});

test('depositing and withdrawing change the stored cents and the loaded balance', function () {
    $account = Account::factory()->create(['balance' => 100]);

    $account->deposit('25.50');
    $account->withdraw(0.25);

    expect($account->balance)->toBe('125.25')
        ->and($account->fresh()->balance_in_cents)->toBe(12525);
});

test('the distance to the minimum is the same amount above and below it', function (string $balance, bool $isBelow) {
    $account = Account::factory()->create(['balance' => $balance, 'min_balance' => '100.10']);

    expect($account->isBelowMinimum())->toBe($isBelow)
        ->and($account->distanceToMinimum())->toBe('50.05');
})->with([
    'above the minimum' => ['150.15', false],
    'below the minimum' => ['50.05', true],
]);

test('the distance to the minimum is zero when the balance equals the minimum', function () {
    $account = Account::factory()->create(['balance' => '0.30', 'min_balance' => '0.30']);

    expect($account->isBelowMinimum())->toBeFalse()
        ->and($account->distanceToMinimum())->toBe('0.00');
});

test('the monthly flow gives each month its net and whether income covered the expenses', function () {
    $account = Account::factory()->create();
    $category = Category::factory()->create();
    $thisMonth = now()->startOfMonth()->addDay()->toDateString();
    $lastMonth = now()->startOfMonth()->subMonth()->addDay()->toDateString();
    Income::factory()->for($account)->for($category)->create(['amount' => '0.30', 'date' => $thisMonth]);
    Expense::factory()->for($account)->for($category)->create(['amount' => '0.10', 'date' => $thisMonth]);
    Income::factory()->for($account)->for($category)->create(['amount' => '0.10', 'date' => $lastMonth]);
    Expense::factory()->for($account)->for($category)->create(['amount' => '0.30', 'date' => $lastMonth]);

    $flow = $account->monthlyFlow();

    expect($flow->last())->toMatchArray(['income' => 0.3, 'expenses' => 0.1, 'net' => 0.2, 'surplus' => true])
        ->and($flow->get(4))->toMatchArray(['net' => 0.2, 'surplus' => false])
        ->and($flow->first())->toMatchArray(['net' => 0.0, 'surplus' => true]);
});
