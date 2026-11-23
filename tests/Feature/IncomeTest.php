<?php

use App\Models\Account;
use App\Models\Category;
use App\Models\Income;
use App\Models\User;
use Illuminate\Support\Carbon;

test('amount is kept with two decimals and date is a date', function () {
    $income = Income::factory()->create(['amount' => 150, 'date' => '2026-11-10'])->fresh();

    expect($income->amount)->toBe(150.00)
        ->and($income->date)->toBeInstanceOf(Carbon::class)
        ->and($income->date->toDateString())->toBe('2026-11-10');
});

test('an income belongs to an account and a category', function () {
    $account = Account::factory()->create();
    $category = Category::factory()->create();
    $income = Income::factory()->for($account)->for($category)->create();

    expect($income->account->is($account))->toBeTrue()
        ->and($income->category->is($category))->toBeTrue()
        ->and($account->incomes->first()->is($income))->toBeTrue()
        ->and($category->incomes->first()->is($income))->toBeTrue();
});

test('the user\'s incomes are the ones received into their own accounts', function () {
    $user = User::factory()->create();
    $own = Income::factory()->for(Account::factory()->for($user))->create();
    Income::factory()->for(Account::factory())->create();

    expect($user->incomes)->toHaveCount(1)
        ->and($user->incomes->first()->is($own))->toBeTrue();
});

test('incomes can be mass assigned with their own fields', function () {
    $income = Income::create([
        'amount' => '25.50',
        'concept' => 'Gift',
        'date' => '2026-11-01',
        'account_id' => Account::factory()->create()->id,
        'category_id' => Category::factory()->create()->id,
    ]);

    $this->assertDatabaseHas('incomes', ['id' => $income->id, 'concept' => 'Gift', 'amount_in_cents' => 2550]);
});

test('an income amount is stored as whole cents and read back as a decimal', function () {
    $income = Income::factory()->create(['amount' => 12.34])->fresh();

    expect($income->amount_in_cents)->toBe(1234)
        ->and($income->amount)->toBe(12.34);
});

test('income amounts that floats cannot hold exactly are stored to the cent', function (string|float $amount, int $cents) {
    $income = Income::factory()->create(['amount' => $amount])->fresh();

    expect($income->amount_in_cents)->toBe($cents);
})->with([
    'a tenth' => [0.1, 10],
    'a string from a form' => ['0.30', 30],
    'the input maximum' => ['999999.99', 99999999],
]);
