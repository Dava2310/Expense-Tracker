<?php

use App\Models\Account;
use App\Models\Category;
use App\Models\Currency;
use App\Models\Expense;
use App\Models\User;

test('a user creates an account that starts at zero, whatever balance is submitted', function () {
    $user = User::factory()->create();
    $currency = Currency::factory()->for($user)->create();

    $this->actingAs($user)
        ->post(route('accounts.store'), [
            'name' => 'Everyday',
            'bank' => 'Harbor Bank',
            'details' => 'Card ending 4821',
            'currency_id' => $currency->id,
            'min_balance' => '500.00',
            'balance' => '9999.00',
        ])
        ->assertRedirect(route('accounts.index'))
        ->assertSessionHas('status', 'account-created');

    $account = Account::firstWhere('name', 'Everyday');

    expect($account->user_id)->toBe($user->id)
        ->and($account->currency_id)->toBe($currency->id)
        ->and($account->min_balance)->toEqual(500)
        ->and($account->balance)->toEqual(0);
});

test('creating an account without a name, a bank or a currency shows what to fix and saves nothing', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('accounts.store'), [])
        ->assertSessionHasErrors([
            'name' => 'The account name field is required.',
            'bank' => 'Enter the name of the bank, or “Cash”.',
            'currency_id' => 'Choose the currency of the account.',
        ]);

    expect(Account::count())->toBe(0);
});

test('an account cannot be created in a currency of another user', function () {
    $user = User::factory()->create();
    $strangersCurrency = Currency::factory()->for(User::factory())->create();

    $this->actingAs($user)
        ->post(route('accounts.store'), ['name' => 'Everyday', 'bank' => 'Harbor Bank', 'currency_id' => $strangersCurrency->id])
        ->assertSessionHasErrors(['currency_id' => 'Choose one of your own currencies.']);

    expect(Account::count())->toBe(0);
});

test('updating an account changes its details but never its balance', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create(['name' => 'Old name', 'balance' => 250]);

    $this->actingAs($user)
        ->patch(route('accounts.update', $account), ['name' => 'New name', 'bank' => 'Other Bank', 'min_balance' => '100.00', 'balance' => '999.00'])
        ->assertRedirect(route('accounts.show', $account))
        ->assertSessionHas('status', 'account-updated');

    $account->refresh();

    expect($account->name)->toBe('New name')
        ->and($account->bank)->toBe('Other Bank')
        ->and($account->min_balance)->toEqual(100)
        ->and($account->balance)->toEqual(250);
});

test('deactivating an account keeps its history and hides it from the account list and the entry forms', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create(['name' => 'Old wallet']);
    $expense = Expense::factory()->for($account)->for(Category::factory()->for($user))->create();

    $this->actingAs($user)
        ->delete(route('accounts.destroy', $account))
        ->assertRedirect(route('accounts.index'))
        ->assertSessionHas('status', 'account-deactivated');

    $this->assertModelExists($expense);
    expect(Account::withTrashed()->find($account->id)->trashed())->toBeTrue();
    $this->get(route('accounts.index'))->assertOk()->assertDontSee('Old wallet');
    $this->get(route('expenses.create'))->assertOk()->assertDontSee('Old wallet');
    $this->get(route('expenses.index'))->assertOk()->assertSee('Old wallet');
});

test('a deactivated account can no longer be edited or deactivated again', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->inactive()->create();

    $this->actingAs($user)->get(route('accounts.edit', $account))->assertNotFound();
    $this->delete(route('accounts.destroy', $account))->assertNotFound();
});

test('a user cannot see, edit or deactivate the account of another user', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for(User::factory())->create(['name' => 'Secret savings']);

    $this->actingAs($user);
    $this->get(route('accounts.show', $account))->assertForbidden();
    $this->get(route('accounts.edit', $account))->assertForbidden();
    $this->patch(route('accounts.update', $account), ['name' => 'Hacked', 'bank' => 'Bank'])->assertForbidden();
    $this->delete(route('accounts.destroy', $account))->assertForbidden();

    $account->refresh();

    expect($account->name)->toBe('Secret savings')
        ->and($account->trashed())->toBeFalse();
});

test('balances stay exact to the cent when many small amounts are added and taken out', function () {
    $account = Account::factory()->create(['balance' => 0]);

    foreach (range(1, 100) as $step) {
        $account->deposit(0.10);
    }

    expect($account->fresh()->balance)->toEqual(10);

    foreach (range(1, 100) as $step) {
        $account->withdraw(0.10);
    }

    expect($account->fresh()->balance)->toEqual(0);
});
