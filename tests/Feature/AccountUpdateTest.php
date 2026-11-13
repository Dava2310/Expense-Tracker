<?php

use App\Models\Account;
use App\Models\User;

test('owner sees the edit form with the current values', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create(['name' => 'Savings', 'bank' => 'Test Bank']);

    $this->actingAs($user)
        ->get(route('accounts.edit', $account))
        ->assertOk()
        ->assertSee('Savings')
        ->assertSee('Test Bank');
});

test('owner updates the editable fields', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create();

    $this->actingAs($user)
        ->patch(route('accounts.update', $account), [
            'name' => 'New name',
            'bank' => 'New bank',
            'details' => 'New details',
            'min_balance' => '25.50',
        ])
        ->assertRedirect(route('accounts.show', $account))
        ->assertSessionHas('status', 'account-updated');

    $this->assertDatabaseHas('accounts', [
        'id' => $account->id,
        'name' => 'New name',
        'bank' => 'New bank',
        'details' => 'New details',
        'min_balance' => 25.50,
    ]);
});

test('a submitted balance is ignored', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create(['balance' => 100]);

    $this->actingAs($user)->patch(route('accounts.update', $account), [
        'name' => 'New name',
        'bank' => 'New bank',
        'details' => 'New details',
        'min_balance' => '25.50',
        'balance' => '999',
    ]);

    expect($account->fresh()->balance)->toEqual(100);
});

test('another user cannot see or update the account', function () {
    $owner = User::factory()->create();
    $account = Account::factory()->for($owner)->create(['name' => 'Original']);
    $intruder = User::factory()->create();

    $this->actingAs($intruder)
        ->get(route('accounts.edit', $account))
        ->assertNotFound();

    $this->actingAs($intruder)
        ->patch(route('accounts.update', $account), [
            'name' => 'Hacked',
            'bank' => 'New bank',
            'details' => 'New details',
            'min_balance' => '25.50',
        ])
        ->assertNotFound();

    expect($account->fresh()->name)->toBe('Original');
});

test('invalid data is rejected and the account is unchanged', function (string $field, string $value) {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create(['name' => 'Original']);
    $valid = [
        'name' => 'New name',
        'bank' => 'New bank',
        'details' => 'New details',
        'min_balance' => '25.50',
    ];

    $this->actingAs($user)
        ->patch(route('accounts.update', $account), [...$valid, $field => $value])
        ->assertSessionHasErrors($field);

    expect($account->fresh()->name)->toBe('Original');
})->with([
    'missing name' => ['name', ''],
    'name too long' => ['name', str_repeat('a', 256)],
    'negative minimum balance' => ['min_balance', '-5'],
    'too many decimals' => ['min_balance', '10.555'],
]);

test('guests are redirected to login', function () {
    $account = Account::factory()->create();

    $this->get(route('accounts.edit', $account))->assertRedirect(route('login'));
    $this->patch(route('accounts.update', $account), [])->assertRedirect(route('login'));
});

test('details can be left empty when updating', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create(['details' => 'Old details']);

    $this->actingAs($user)
        ->patch(route('accounts.update', $account), [
            'name' => 'New name',
            'bank' => 'New bank',
            'details' => '',
            'min_balance' => '25.50',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('accounts.show', $account));

    expect($account->fresh()->details)->toBe('');
});

test('the edit form is prefilled and does not offer the balance', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create([
        'name' => 'Everyday',
        'bank' => 'Harbor Bank',
        'details' => 'Card ending 4821',
        'min_balance' => 500,
    ]);

    $this->actingAs($user)
        ->get(route('accounts.edit', $account))
        ->assertOk()
        ->assertSee('Edit account')
        ->assertSee('value="Everyday"', false)
        ->assertSee('value="Harbor Bank"', false)
        ->assertSee('Card ending 4821')
        ->assertSee('value="500.00"', false)
        ->assertSee('Save changes')
        ->assertSee('name="_method" value="PATCH"', false)
        ->assertDontSee('name="balance"', false)
        ->assertDontSee('New accounts start at');
});

test('invalid edits are rejected and the errors are shown', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create(['name' => 'Original']);

    $this->actingAs($user)
        ->from(route('accounts.edit', $account))
        ->patch(route('accounts.update', $account), [
            'name' => '',
            'bank' => 'Bank',
            'min_balance' => '-1',
        ])
        ->assertRedirect(route('accounts.edit', $account))
        ->assertSessionHasErrors(['name', 'min_balance']);

    expect($account->fresh()->name)->toBe('Original');

    $this->actingAs($user)
        ->get(route('accounts.edit', $account))
        ->assertSee('aria-invalid="true"', false);
});

test('clearing the minimum balance resets it to zero', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create(['min_balance' => 80]);

    $this->actingAs($user)
        ->patch(route('accounts.update', $account), [
            'name' => 'New name',
            'bank' => 'New bank',
            'min_balance' => '',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('accounts.show', $account));

    expect($account->fresh()->min_balance)->toEqual(0);
});
