<?php

use App\Models\Account;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});

test('dashboard shows only the user\'s accounts with balances and minimums', function () {
    $user = User::factory()->create();
    Account::factory()->for($user)->create([
        'name' => 'Everyday', 'bank' => 'Harbor Bank', 'balance' => 2480.15, 'min_balance' => 500,
    ]);
    Account::factory()->for($user)->create([
        'name' => 'Savings', 'bank' => 'Harbor Bank', 'balance' => 12350, 'min_balance' => 5000,
    ]);
    Account::factory()->create(['name' => 'Someone else\'s account']);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Everyday')
        ->assertSee('€2,480.15')
        ->assertSee('Minimum €500.00')
        ->assertSee('Savings')
        ->assertSee('€12,350.00')
        ->assertSee('Total balance')
        ->assertSee('€14,830.15')
        ->assertSee('across 2 accounts')
        ->assertDontSee('Someone else\'s account');
});

test('an account under its minimum is flagged', function () {
    $user = User::factory()->create();
    Account::factory()->for($user)->create([
        'name' => 'Wallet', 'balance' => 86.40, 'min_balance' => 100,
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Below €100.00 minimum')
        ->assertDontSee('Minimum €100.00');
});

test('an account exactly at its minimum is not flagged', function () {
    $user = User::factory()->create();
    Account::factory()->for($user)->create(['balance' => 100, 'min_balance' => 100]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertDontSee('Below €100.00 minimum')
        ->assertSee('Minimum €100.00');
});

test('dashboard invites users without accounts to create one', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('No accounts yet')
        ->assertSee(route('accounts.create'), false);
});

test('navigation links to working pages, disables the rest and offers profile and log out', function () {
    $user = User::factory()->create(['name' => 'Alex Rivera', 'email' => 'alex@example.com']);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('aria-current="page"', false)
        ->assertSee(route('accounts.index'), false)
        ->assertSee(route('incomes.index'), false)
        ->assertSee(route('profile.edit'), false)
        ->assertSee('action="'.route('logout').'"', false)
        ->assertSee('Alex Rivera')
        ->assertSee('alex@example.com')
        ->assertSeeInOrder(['Expenses', 'Incomes', 'Categories'])
        ->assertSee('aria-disabled="true"', false);
});
