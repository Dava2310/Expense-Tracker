<?php

use App\Models\Account;
use App\Models\Currency;
use App\Models\User;

test('a user creates a currency that belongs to them', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('currencies.store'), ['name' => 'Euro', 'symbol' => '€'])
        ->assertRedirect(route('currencies.index'))
        ->assertSessionHas('status', 'currency-created');

    expect(Currency::firstWhere('name', 'Euro')->user_id)->toBe($user->id);
});

test('a user updates the name and the symbol of a currency', function () {
    $user = User::factory()->create();
    $currency = Currency::factory()->for($user)->create(['name' => 'Euro', 'symbol' => '€']);

    $this->actingAs($user)
        ->patch(route('currencies.update', $currency), ['name' => 'US Dollar', 'symbol' => '$'])
        ->assertRedirect(route('currencies.index'))
        ->assertSessionHas('status', 'currency-updated');

    $currency->refresh();

    expect($currency->name)->toBe('US Dollar')
        ->and($currency->symbol)->toBe('$');
});

test('a currency used by an account, active or inactive, cannot be deleted', function () {
    $user = User::factory()->create();
    $usedByActive = Currency::factory()->for($user)->create();
    $usedByInactive = Currency::factory()->for($user)->create();
    Account::factory()->for($user)->for($usedByActive)->create();
    Account::factory()->for($user)->for($usedByInactive)->inactive()->create();

    $this->actingAs($user);
    $this->delete(route('currencies.destroy', $usedByActive))->assertRedirect(route('currencies.index'));
    $this->delete(route('currencies.destroy', $usedByInactive))->assertRedirect(route('currencies.index'));

    $this->assertModelExists($usedByActive);
    $this->assertModelExists($usedByInactive);
});

test('a currency that no account uses is deleted', function () {
    $user = User::factory()->create();
    $currency = Currency::factory()->for($user)->create();

    $this->actingAs($user)
        ->delete(route('currencies.destroy', $currency))
        ->assertRedirect(route('currencies.index'))
        ->assertSessionHas('status', 'currency-deleted');

    $this->assertModelMissing($currency);
});

test('the total balance of a currency adds up its accounts, inactive ones included', function () {
    $user = User::factory()->create();
    $currency = Currency::factory()->for($user)->create();
    Account::factory()->for($user)->for($currency)->create(['balance' => 100.10]);
    Account::factory()->for($user)->for($currency)->inactive()->create(['balance' => 50.20]);
    Account::factory()->for($user)->create(['balance' => 999]);

    expect($currency->totalBalance())->toEqual(150.30);
});

test('a user cannot see, edit or delete the currency of another user', function () {
    $user = User::factory()->create();
    $currency = Currency::factory()->for(User::factory())->create(['name' => 'Private']);

    $this->actingAs($user);
    $this->get(route('currencies.show', $currency))->assertForbidden();
    $this->get(route('currencies.edit', $currency))->assertForbidden();
    $this->patch(route('currencies.update', $currency), ['name' => 'Hacked', 'symbol' => '#'])->assertForbidden();
    $this->delete(route('currencies.destroy', $currency))->assertForbidden();

    expect($currency->fresh()->name)->toBe('Private');
});
