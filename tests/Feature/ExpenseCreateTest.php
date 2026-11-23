<?php

use App\Models\Account;
use App\Models\Category;
use App\Models\Currency;
use App\Models\Expense;
use App\Models\User;

/**
 * Valid input for an expense, which each test can override.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function validExpenseInput(Account $account, Category $category, array $overrides = []): array
{
    return [
        'amount' => '84.30',
        'concept' => 'Weekly groceries',
        'date' => '2026-11-13',
        'account_id' => $account->id,
        'category_id' => $category->id,
        ...$overrides,
    ];
}

test('guests are redirected to the login page', function () {
    $this->get(route('expenses.create'))->assertRedirect(route('login'));
    $this->post(route('expenses.store'), [])->assertRedirect(route('login'));
});

test('the new expense form lists only the active accounts and the categories of the user', function () {
    $user = User::factory()->create();
    Account::factory()->for($user)->create(['name' => 'Everyday']);
    Account::factory()->for($user)->inactive()->create(['name' => 'Closed account']);
    Account::factory()->create(['name' => 'Somebody elses']);
    Category::factory()->for($user)->create(['name' => 'Groceries']);
    Category::factory()->create(['name' => 'Foreign category']);

    $this->actingAs($user)
        ->get(route('expenses.create'))
        ->assertOk()
        ->assertSee('New expense')
        ->assertSee('The amount is deducted from the account you choose.')
        ->assertSee('name="amount"', false)
        ->assertSee('name="concept"', false)
        ->assertSee('name="date"', false)
        ->assertSee('Pay from account')
        ->assertSee('Add expense')
        ->assertSee('Everyday')
        ->assertSee('Groceries')
        ->assertDontSee('Closed account')
        ->assertDontSee('Somebody elses')
        ->assertDontSee('Foreign category');
});

test('a user creates an expense and its amount is deducted from the account balance', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create(['balance' => 100]);
    $category = Category::factory()->for($user)->create();

    $this->actingAs($user)
        ->post(route('expenses.store'), validExpenseInput($account, $category))
        ->assertRedirect(route('expenses.index'))
        ->assertSessionHas('status', 'expense-created');

    $expense = Expense::firstWhere('concept', 'Weekly groceries');

    expect($expense->account_id)->toBe($account->id)
        ->and($expense->category_id)->toBe($category->id)
        ->and($expense->amount)->toEqual(84.30)
        ->and($expense->date->toDateString())->toBe('2026-11-13')
        ->and($account->refresh()->balance)->toEqual(15.70);
});

test('an expense can use the whole balance of the account', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create(['balance' => 84.30]);
    $category = Category::factory()->for($user)->create();

    $this->actingAs($user)
        ->post(route('expenses.store'), validExpenseInput($account, $category))
        ->assertSessionHasNoErrors();

    expect($account->refresh()->balance)->toEqual(0);
});

test('an expense bigger than the account balance is rejected', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create(['name' => 'Wallet', 'balance' => 50]);
    $category = Category::factory()->for($user)->create();

    $this->actingAs($user)
        ->from(route('expenses.create'))
        ->post(route('expenses.store'), validExpenseInput($account, $category))
        ->assertRedirect(route('expenses.create'))
        ->assertSessionHasErrors(['amount' => 'Wallet only has €50.00 available.']);

    expect(Expense::count())->toBe(0)
        ->and($account->refresh()->balance)->toEqual(50);
});

test('invalid input is rejected, nothing is saved and the errors are shown', function (array $overrides, string $invalid) {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create(['balance' => 100]);
    $category = Category::factory()->for($user)->create();

    $this->actingAs($user)
        ->from(route('expenses.create'))
        ->post(route('expenses.store'), validExpenseInput($account, $category, $overrides))
        ->assertRedirect(route('expenses.create'))
        ->assertSessionHasErrors($invalid);

    expect(Expense::count())->toBe(0)
        ->and($account->refresh()->balance)->toEqual(100);

    $this->actingAs($user)
        ->get(route('expenses.create'))
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

test('an account of another user cannot pay an expense', function () {
    $user = User::factory()->create();
    $foreignAccount = Account::factory()->create(['balance' => 100]);
    $category = Category::factory()->for($user)->create();

    $this->actingAs($user)
        ->post(route('expenses.store'), validExpenseInput($foreignAccount, $category))
        ->assertSessionHasErrors('account_id');

    expect(Expense::count())->toBe(0)
        ->and($foreignAccount->refresh()->balance)->toEqual(100);
});

test('a category of another user cannot be used', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create(['balance' => 100]);
    $foreignCategory = Category::factory()->create();

    $this->actingAs($user)
        ->post(route('expenses.store'), validExpenseInput($account, $foreignCategory))
        ->assertSessionHasErrors('category_id');

    expect(Expense::count())->toBe(0)
        ->and($account->refresh()->balance)->toEqual(100);
});

test('a deactivated account cannot pay an expense', function () {
    $user = User::factory()->create();
    $inactiveAccount = Account::factory()->for($user)->inactive()->create(['balance' => 100]);
    $category = Category::factory()->for($user)->create();

    $this->actingAs($user)
        ->post(route('expenses.store'), validExpenseInput($inactiveAccount, $category))
        ->assertSessionHasErrors('account_id');

    expect(Expense::count())->toBe(0)
        ->and($inactiveAccount->refresh()->balance)->toEqual(100);
});

test('the new expense form puts the account before the amount and uses each account\'s currency', function () {
    $user = User::factory()->create();
    $dollars = Currency::factory()->for($user)->create(['symbol' => '$']);
    $account = Account::factory()->for($user)->for($dollars)->create(['name' => 'Everyday', 'balance' => 1250]);

    $this->actingAs($user)
        ->get(route('expenses.create'))
        ->assertSeeInOrder(['name="account_id"', 'name="amount"', 'name="concept"', 'name="category_id"', 'name="date"'], false)
        ->assertSee('Everyday · $1,250.00')
        ->assertSee('x-text="symbol"', false)
        ->assertSee('\u0022'.$account->id.'\u0022:\u0022$\u0022', false);
});

test('an expense is accepted when it equals the balance to the cent and rejected one cent above it', function (string $amount, bool $accepted) {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create(['name' => 'Wallet', 'balance' => '10.00']);
    $category = Category::factory()->for($user)->create();

    $response = $this->actingAs($user)
        ->from(route('expenses.create'))
        ->post(route('expenses.store'), validExpenseInput($account, $category, ['amount' => $amount]));

    if ($accepted) {
        $response->assertSessionHasNoErrors();
        expect($account->refresh()->balance)->toBe(0.00);
    } else {
        $response->assertSessionHasErrors(['amount' => 'Wallet only has €10.00 available.']);
        expect(Expense::count())->toBe(0)
            ->and($account->refresh()->balance)->toBe(10.00);
    }
})->with([
    'equal to the balance' => ['10.00', true],
    'one cent more' => ['10.01', false],
]);
