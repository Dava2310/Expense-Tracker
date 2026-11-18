<?php

use App\Models\Account;
use App\Models\Currency;
use App\Models\User;

test('the delete button is disabled for a currency used by accounts', function () {
    $user = User::factory()->create();
    $currency = Currency::factory()->for($user)->create(['name' => 'Euro']);
    Account::factory()->for($user)->create(['currency_id' => $currency->id]);

    $this->actingAs($user)
        ->get(route('currencies.index'))
        ->assertOk()
        ->assertSee('In use by 1 account, so it can’t be deleted', false)
        ->assertDontSee("open-modal', 'confirm-currency-deletion'", false);

    $this->actingAs($user)
        ->get(route('currencies.show', $currency))
        ->assertOk()
        ->assertSee('In use by 1 account, so it can’t be deleted', false);
});

test('the delete button is enabled for a currency without accounts', function () {
    $user = User::factory()->create();
    $currency = Currency::factory()->for($user)->create();

    $this->actingAs($user)
        ->get(route('currencies.index'))
        ->assertOk()
        ->assertDontSee('so it can’t be deleted', false);

    $this->actingAs($user)
        ->get(route('currencies.show', $currency))
        ->assertOk()
        ->assertDontSee('so it can’t be deleted', false);
});
