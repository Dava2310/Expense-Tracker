<?php

use App\Models\Account;
use App\Models\Category;
use App\Models\Currency;
use App\Models\Income;
use App\Models\User;

/**
 * Valid input for an income, which each test can override.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function validIncomeInput(Account $account, Category $category, array $overrides = []): array
{
    return [
        'amount' => '600.50',
        'concept' => 'Design workshop',
        'date' => '2026-11-13',
        'account_id' => $account->id,
        'category_id' => $category->id,
        ...$overrides,
    ];
}

test('guests are redirected to the login page', function () {
    $this->get(route('incomes.create'))->assertRedirect(route('login'));
    $this->post(route('incomes.store'), [])->assertRedirect(route('login'));
});

test('the new income form lists only the active accounts and the categories of the user', function () {
    $user = User::factory()->create();
    Account::factory()->for($user)->create(['name' => 'Everyday']);
    Account::factory()->for($user)->inactive()->create(['name' => 'Closed account']);
    Account::factory()->create(['name' => 'Somebody elses']);
    Category::factory()->for($user)->create(['name' => 'Freelance']);
    Category::factory()->create(['name' => 'Foreign category']);

    $this->actingAs($user)
        ->get(route('incomes.create'))
        ->assertOk()
        ->assertSee('New income')
        ->assertSee('name="amount"', false)
        ->assertSee('name="concept"', false)
        ->assertSee('name="date"', false)
        ->assertSee('Everyday')
        ->assertSee('Freelance')
        ->assertDontSee('Closed account')
        ->assertDontSee('Somebody elses')
        ->assertDontSee('Foreign category');
});

test('a user creates an income and its amount is added to the account balance', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create(['balance' => 100]);
    $category = Category::factory()->for($user)->create();

    $this->actingAs($user)
        ->post(route('incomes.store'), validIncomeInput($account, $category))
        ->assertRedirect(route('incomes.index'))
        ->assertSessionHas('status', 'income-created');

    $income = Income::firstWhere('concept', 'Design workshop');

    expect($income->account_id)->toBe($account->id)
        ->and($income->category_id)->toBe($category->id)
        ->and($income->amount)->toEqual(600.50)
        ->and($income->date->toDateString())->toBe('2026-11-13')
        ->and($account->refresh()->balance)->toEqual(700.50);
});

test('invalid input is rejected, nothing is saved and the errors are shown', function (array $overrides, string $invalid) {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create(['balance' => 100]);
    $category = Category::factory()->for($user)->create();

    $this->actingAs($user)
        ->from(route('incomes.create'))
        ->post(route('incomes.store'), validIncomeInput($account, $category, $overrides))
        ->assertRedirect(route('incomes.create'))
        ->assertSessionHasErrors($invalid);

    expect(Income::count())->toBe(0)
        ->and($account->refresh()->balance)->toEqual(100);

    $this->actingAs($user)
        ->get(route('incomes.create'))
        ->assertSee('aria-invalid="true"', false);
})->with([
    'missing amount' => [['amount' => ''], 'amount'],
    'zero amount' => [['amount' => '0'], 'amount'],
    'negative amount' => [['amount' => '-5'], 'amount'],
    'too many decimals' => [['amount' => '1.234'], 'amount'],
    'not a number' => [['amount' => 'lots'], 'amount'],
    'amount above the limit' => [['amount' => '1000000'], 'amount'],
    'missing concept' => [['concept' => ''], 'concept'],
    'missing date' => [['date' => ''], 'date'],
    'invalid date' => [['date' => '13/11/2026'], 'date'],
    'missing account' => [['account_id' => ''], 'account_id'],
    'unknown category' => [['category_id' => 999999], 'category_id'],
]);

test('an account of another user cannot receive an income', function () {
    $user = User::factory()->create();
    $foreignAccount = Account::factory()->create(['balance' => 100]);
    $category = Category::factory()->for($user)->create();

    $this->actingAs($user)
        ->post(route('incomes.store'), validIncomeInput($foreignAccount, $category))
        ->assertSessionHasErrors('account_id');

    expect(Income::count())->toBe(0)
        ->and($foreignAccount->refresh()->balance)->toEqual(100);
});

test('a category of another user cannot be used', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create(['balance' => 100]);
    $foreignCategory = Category::factory()->create();

    $this->actingAs($user)
        ->post(route('incomes.store'), validIncomeInput($account, $foreignCategory))
        ->assertSessionHasErrors('category_id');

    expect(Income::count())->toBe(0)
        ->and($account->refresh()->balance)->toEqual(100);
});

test('a deactivated account cannot receive an income', function () {
    $user = User::factory()->create();
    $inactiveAccount = Account::factory()->for($user)->inactive()->create(['balance' => 100]);
    $category = Category::factory()->for($user)->create();

    $this->actingAs($user)
        ->post(route('incomes.store'), validIncomeInput($inactiveAccount, $category))
        ->assertSessionHasErrors('account_id');

    expect(Income::count())->toBe(0)
        ->and($inactiveAccount->refresh()->balance)->toEqual(100);
});

test('the new income form puts the account before the amount and uses each account\'s currency', function () {
    $user = User::factory()->create();
    $dollars = Currency::factory()->for($user)->create(['symbol' => '$']);
    $account = Account::factory()->for($user)->for($dollars)->create(['name' => 'Everyday', 'balance' => 1250]);

    $this->actingAs($user)
        ->get(route('incomes.create'))
        ->assertSeeInOrder(['name="account_id"', 'name="amount"', 'name="concept"', 'name="category_id"', 'name="date"'], false)
        ->assertSee('Everyday · $1,250.00')
        ->assertSee('x-text="symbol"', false)
        ->assertSee('\u0022'.$account->id.'\u0022:\u0022$\u0022', false);
});
