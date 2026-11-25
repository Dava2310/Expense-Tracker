<?php

use App\Models\Account;
use App\Models\Category;
use App\Models\Currency;
use App\Models\Expense;
use App\Models\Income;
use App\Models\Transfer;
use App\Models\User;

test('seeder creates the expected records without extra parents', function () {
    $this->seed();

    expect(User::count())->toBe(11)
        ->and(Currency::count())->toBe(11)
        ->and(Account::count())->toBe(22)
        ->and(Category::count())->toBeBetween(11, 33)
        ->and(Expense::count())->toBeBetween(11, 55)
        ->and(Income::count())->toBeBetween(11, 55)
        ->and(Transfer::count())->toBeBetween(0, 22);
});

test('seeded incomes and expenses use a category of the user that owns their account', function () {
    $this->seed();

    $entries = Income::with(['account', 'category'])->get()
        ->concat(Expense::with(['account', 'category'])->get());

    $entries->each(fn ($entry) => expect($entry->category->user_id)->toBe($entry->account->user_id));
});

test('seeder creates the test user', function () {
    $this->seed();

    $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
});
