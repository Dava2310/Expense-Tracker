<?php

use App\Models\Account;
use App\Models\Category;
use App\Models\Expense;
use App\Models\Income;
use App\Models\User;

test('seeder creates the expected records without extra parents', function () {
    $this->seed();

    expect(User::count())->toBe(11)
        ->and(Account::count())->toBe(10)
        ->and(Category::count())->toBe(10)
        ->and(Expense::count())->toBe(10)
        ->and(Income::count())->toBe(10);
});

test('seeder creates the test user', function () {
    $this->seed();

    $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
});
