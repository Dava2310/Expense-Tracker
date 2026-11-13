<?php

use App\Models\Account;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $this->get(route('accounts.index'))->assertRedirect(route('login'));
});

test('accounts table lists only the user\'s accounts with formatted amounts', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create([
        'name' => 'Everyday', 'bank' => 'Harbor Bank', 'details' => 'Main checking account',
        'balance' => 2480.15, 'min_balance' => 500,
    ]);
    Account::factory()->create(['name' => 'Someone else\'s account']);

    $this->actingAs($user)
        ->get(route('accounts.index'))
        ->assertOk()
        ->assertSee('Everyday')
        ->assertSee('Harbor Bank')
        ->assertSee('Main checking account')
        ->assertSee('€2,480.15')
        ->assertSee('€500.00')
        ->assertSee('On track')
        ->assertSee(route('accounts.show', $account), false)
        ->assertSee(route('accounts.edit', $account), false)
        ->assertSee(route('accounts.create'), false)
        ->assertDontSee('Someone else\'s account');
});

test('an account under its minimum shows a warning status', function () {
    $user = User::factory()->create();
    Account::factory()->for($user)->create(['balance' => 10, 'min_balance' => 50]);

    $this->actingAs($user)
        ->get(route('accounts.index'))
        ->assertSee('Below minimum')
        ->assertDontSee('On track');
});

test('accounts table explains when there are no accounts', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('accounts.index'))
        ->assertOk()
        ->assertSee('You have no accounts yet.');
});

test('flash messages are shown after creating and updating an account', function (string $status, string $message) {
    $this->actingAs(User::factory()->create())
        ->withSession(['status' => $status])
        ->get(route('accounts.index'))
        ->assertSee($message);
})->with([
    'created' => ['account-created', 'Account created.'],
    'updated' => ['account-updated', 'Account updated.'],
    'deactivated' => ['account-deactivated', 'Account deactivated.'],
]);

test('a dash is shown when an account has no details', function () {
    $user = User::factory()->create();
    Account::factory()->for($user)->create(['details' => '']);

    $this->actingAs($user)
        ->get(route('accounts.index'))
        ->assertSee('md:table-cell">-</td>', false);
});
