<?php

use App\Models\Account;
use App\Models\Category;
use App\Models\Currency;
use App\Models\Expense;
use App\Models\Income;
use App\Models\Transfer;
use App\Models\User;

test('the public pages open without signing in', function () {
    $urls = ['/', '/login', '/register', '/forgot-password', '/reset-password/a-reset-token'];

    $notOk = collect($urls)->mapWithKeys(fn (string $url) => [$url => $this->get($url)->status()])->reject(fn (int $status) => $status === 200);

    expect($notOk->all())->toBe([]);
});

test('guests are redirected to the login page from every private page', function () {
    $urls = [
        '/dashboard', '/profile', '/confirm-password',
        '/accounts', '/accounts/create', '/accounts/1', '/accounts/1/edit',
        '/categories', '/categories/create', '/categories/1/edit',
        '/currencies', '/currencies/create', '/currencies/1', '/currencies/1/edit',
        '/expenses', '/expenses/create', '/expenses/1/edit',
        '/incomes', '/incomes/create', '/incomes/1/edit',
        '/transfers/create', '/transfers/1/edit',
    ];

    $notRedirected = collect($urls)
        ->mapWithKeys(fn (string $url) => [$url => $this->get($url)->headers->get('Location')])
        ->reject(fn (?string $location) => $location === route('login'));

    expect($notRedirected->all())->toBe([]);
});

test('a signed-in user opens every private page', function () {
    $user = User::factory()->create();
    $currency = Currency::factory()->for($user)->create();
    $account = Account::factory()->for($user)->for($currency)->create(['balance' => 100]);
    $savings = Account::factory()->for($user)->for($currency)->create(['balance' => 100]);
    $category = Category::factory()->for($user)->create();
    $expense = Expense::factory()->for($account)->for($category)->create();
    $income = Income::factory()->for($account)->for($category)->create();
    $transfer = Transfer::factory()->create(['from_account_id' => $account->id, 'to_account_id' => $savings->id]);

    $urls = [
        route('dashboard'), route('dashboard', ['inc_exp' => $account->id, 'cat' => $account->id]), route('profile.edit'), route('password.confirm'),
        route('accounts.index'), route('accounts.create'), route('accounts.show', $account), route('accounts.edit', $account),
        route('categories.index'), route('categories.create'), route('categories.edit', $category),
        route('currencies.index'), route('currencies.create'), route('currencies.show', $currency), route('currencies.edit', $currency),
        route('expenses.index'), route('expenses.create'), route('expenses.edit', $expense),
        route('incomes.index'), route('incomes.create'), route('incomes.edit', $income),
        route('transfers.create'), route('transfers.edit', $transfer),
    ];

    $this->actingAs($user);
    $notOk = collect($urls)->mapWithKeys(fn (string $url) => [$url => $this->get($url)->status()])->reject(fn (int $status) => $status === 200);

    expect($notOk->all())->toBe([]);
});
