<?php

use App\Models\Account;
use App\Models\Currency;
use App\Models\User;

test('the total balance adds up the accounts of the currency, inactive ones included', function () {
    $currency = Currency::factory()->create();
    Account::factory()->for($currency)->create(['balance' => '0.10']);
    Account::factory()->for($currency)->create(['balance' => '0.20']);
    Account::factory()->for($currency)->inactive()->create(['balance' => '1000.05']);
    Account::factory()->create(['balance' => '999']);

    expect($currency->totalBalance())->toBe(1000.35);
});

test('the total balance is zero for a currency without accounts', function () {
    expect(Currency::factory()->create()->totalBalance())->toBe(0.00);
});

test('the currency page shows the total balance with the symbol', function () {
    $user = User::factory()->create();
    $currency = Currency::factory()->for($user)->create(['symbol' => '€']);
    Account::factory()->for($user)->for($currency)->create(['balance' => '1234.50']);
    Account::factory()->for($user)->for($currency)->inactive()->create(['balance' => '0.05']);

    $this->actingAs($user)
        ->get(route('currencies.show', $currency))
        ->assertOk()
        ->assertSee('€1,234.55');
});
