<?php

use App\Models\Account;
use App\Models\Currency;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $this->get(route('accounts.create'))->assertRedirect(route('login'));
    $this->post(route('accounts.store'), [])->assertRedirect(route('login'));
});

test('the new account form asks for name, bank, details, currency and minimum balance only', function () {
    $user = User::factory()->create();
    Currency::factory()->for($user)->create(['name' => 'Euro', 'symbol' => '€']);

    $this->actingAs($user)
        ->get(route('accounts.create'))
        ->assertOk()
        ->assertSee('New account')
        ->assertSee('name="name"', false)
        ->assertSee('name="bank"', false)
        ->assertSee('name="details"', false)
        ->assertSee('name="currency_id"', false)
        ->assertSee('Euro (€)')
        ->assertSee('name="min_balance"', false)
        ->assertDontSee('name="balance"', false)
        ->assertSee('New accounts start with an empty balance.');
});

test('a user creates an account that starts at zero', function () {
    $user = User::factory()->create();
    $currency = Currency::factory()->for($user)->create();

    $this->actingAs($user)
        ->post(route('accounts.store'), [
            'name' => 'Everyday',
            'bank' => 'Harbor Bank',
            'details' => 'Card ending 4821',
            'currency_id' => $currency->id,
            'min_balance' => '500.00',
        ])
        ->assertRedirect(route('accounts.index'))
        ->assertSessionHas('status', 'account-created');

    $account = Account::firstWhere('name', 'Everyday');

    expect($account->user_id)->toBe($user->id)
        ->and($account->bank)->toBe('Harbor Bank')
        ->and($account->currency_id)->toBe($currency->id)
        ->and($account->details)->toBe('Card ending 4821')
        ->and($account->min_balance)->toEqual(500)
        ->and($account->balance)->toEqual(0);
});

test('a submitted balance is ignored when creating an account', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('accounts.store'), [
        'name' => 'Savings',
        'bank' => 'Harbor Bank',
        'currency_id' => Currency::factory()->for($user)->create()->id,
        'min_balance' => '100',
        'balance' => '999',
    ]);

    expect(Account::firstWhere('name', 'Savings')->balance)->toEqual(0);
});

test('details are optional', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('accounts.store'), [
            'name' => 'Wallet',
            'bank' => 'Cash',
            'currency_id' => Currency::factory()->for($user)->create()->id,
            'details' => '',
            'min_balance' => '20',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('accounts.index'));

    expect(Account::firstWhere('name', 'Wallet')->details)->toBe('');
});

test('the minimum balance defaults to zero when left empty or omitted', function (array $extra) {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('accounts.store'), ['name' => 'Wallet', 'bank' => 'Cash', 'currency_id' => Currency::factory()->for($user)->create()->id, ...$extra])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('accounts.index'));

    expect(Account::firstWhere('name', 'Wallet')->min_balance)->toEqual(0);
})->with([
    'empty' => [['min_balance' => '']],
    'omitted' => [[]],
]);

test('invalid input is rejected and the errors are shown', function (array $input, array $invalid) {
    $user = User::factory()->create();
    $input += ['currency_id' => Currency::factory()->for($user)->create()->id];

    $this->actingAs($user)
        ->from(route('accounts.create'))
        ->post(route('accounts.store'), $input)
        ->assertRedirect(route('accounts.create'))
        ->assertSessionHasErrors($invalid);

    expect(Account::count())->toBe(0);

    $this->actingAs($user)
        ->get(route('accounts.create'))
        ->assertSee('aria-invalid="true"', false);
})->with([
    'missing name' => [['bank' => 'Bank', 'min_balance' => '10'], ['name']],
    'missing bank' => [['name' => 'Name', 'min_balance' => '10'], ['bank']],
    'negative minimum balance' => [['name' => 'Name', 'bank' => 'Bank', 'min_balance' => '-5'], ['min_balance']],
    'too many decimals' => [['name' => 'Name', 'bank' => 'Bank', 'min_balance' => '1.234'], ['min_balance']],
    'not a number' => [['name' => 'Name', 'bank' => 'Bank', 'min_balance' => 'lots'], ['min_balance']],
]);

test('the currency is required and must belong to the user', function (string $currency) {
    $user = User::factory()->create();
    $foreign = Currency::factory()->create();

    $this->actingAs($user)
        ->post(route('accounts.store'), [
            'name' => 'Name',
            'bank' => 'Bank',
            'currency_id' => $currency === 'foreign' ? $foreign->id : '',
        ])
        ->assertSessionHasErrors('currency_id');

    expect(Account::count())->toBe(0);
})->with(['missing' => '', 'somebody elses' => 'foreign']);

test('the minimum balance prefix follows the symbols of the user\'s currencies', function () {
    $user = User::factory()->create();
    $currency = Currency::factory()->for($user)->create(['symbol' => '$']);

    $this->actingAs($user)
        ->get(route('accounts.create'))
        ->assertSee('x-text="symbol"', false)
        ->assertSee('\u0022'.$currency->id.'\u0022:\u0022$\u0022', false);
});
