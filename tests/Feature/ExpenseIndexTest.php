<?php

use App\Models\Account;
use App\Models\Category;
use App\Models\Currency;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-11-13 12:00:00'));
});

/**
 * Creates an expense that was paid from the given account and belongs to the given category.
 *
 * @param  array<string, mixed>  $attributes
 */
function expenseFor(Account $account, Category $category, array $attributes = []): Expense
{
    return Expense::factory()->for($account)->for($category)->create($attributes);
}

test('guests are redirected to the login page', function () {
    $this->get(route('expenses.index'))->assertRedirect(route('login'));
});

test('the index lists only the user\'s expenses with their details', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create(['name' => 'Everyday']);
    $category = Category::factory()->for($user)->create(['name' => 'Groceries']);
    $expense = expenseFor($account, $category, ['concept' => 'Weekly groceries', 'amount' => 84.30, 'date' => '2026-11-10']);
    expenseFor(Account::factory()->create(), Category::factory()->create(), ['concept' => 'Secret expense', 'date' => '2026-11-10']);

    $this->actingAs($user)
        ->get(route('expenses.index'))
        ->assertOk()
        ->assertSee('Weekly groceries')
        ->assertSee('Nov 10')
        ->assertSee('Groceries · Everyday')
        ->assertSee('−€84.30')
        ->assertSee(route('expenses.edit', $expense), false)
        ->assertSee(route('expenses.create'), false)
        ->assertDontSee('Secret expense');
});

test('expenses are grouped by month with counts, newest first', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create();
    $category = Category::factory()->for($user)->create();
    expenseFor($account, $category, ['concept' => 'Weekly groceries', 'amount' => 84.30, 'date' => '2026-11-10']);
    expenseFor($account, $category, ['concept' => 'November rent', 'amount' => 1250, 'date' => '2026-11-01']);
    expenseFor($account, $category, ['concept' => 'Gym membership', 'amount' => 35, 'date' => '2026-10-28']);

    $this->actingAs($user)
        ->get(route('expenses.index'))
        ->assertSeeInOrder([
            'November 2026', '2 expenses', 'Weekly groceries', 'November rent',
            'October 2026', '1 expense', 'Gym membership',
        ]);
});

test('the header describes the selected period', function (array $query, string $phrase) {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create();
    $category = Category::factory()->for($user)->create();
    expenseFor($account, $category, ['amount' => 100, 'date' => '2026-11-02']);
    expenseFor($account, $category, ['amount' => 900, 'date' => '2026-10-02']);
    expenseFor($account, $category, ['amount' => 5000, 'date' => '2020-03-05']);

    $this->actingAs($user)
        ->get(route('expenses.index', $query))
        ->assertSee('Spent '.$phrase.' across all accounts');
})->with([
    'default (all time)' => [[], 'in total'],
    'last 3 months' => [['period' => '3m'], 'in the last 3 months'],
    'this month' => [['period' => 'month'], 'this month'],
]);

test('a custom range includes both end dates and is shown in the header', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create();
    $category = Category::factory()->for($user)->create();
    expenseFor($account, $category, ['concept' => 'Before range', 'date' => '2026-02-28']);
    expenseFor($account, $category, ['concept' => 'First day', 'date' => '2026-03-01']);
    expenseFor($account, $category, ['concept' => 'Last day', 'date' => '2026-03-31']);
    expenseFor($account, $category, ['concept' => 'After range', 'date' => '2026-04-01']);

    $this->actingAs($user)
        ->get(route('expenses.index', ['period' => 'custom', 'from' => '2026-03-01', 'to' => '2026-03-31']))
        ->assertSee('First day')
        ->assertSee('Last day')
        ->assertDontSee('Before range')
        ->assertDontSee('After range')
        ->assertSee('Spent between Mar 1, 2026 and Mar 31, 2026');
});

test('expenses can be filtered by account, by category and by both', function (array $filters, array $visible, array $hidden, string $scope) {
    $user = User::factory()->create();
    $everyday = Account::factory()->for($user)->create(['name' => 'Everyday']);
    $wallet = Account::factory()->for($user)->create(['name' => 'Wallet']);
    $groceries = Category::factory()->for($user)->create(['name' => 'Groceries']);
    $health = Category::factory()->for($user)->create(['name' => 'Health']);
    expenseFor($everyday, $groceries, ['concept' => 'Everyday groceries', 'date' => '2026-11-02']);
    expenseFor($everyday, $health, ['concept' => 'Everyday pharmacy', 'date' => '2026-11-03']);
    expenseFor($wallet, $groceries, ['concept' => 'Wallet groceries', 'date' => '2026-11-04']);

    $query = [
        'account' => isset($filters['account']) ? ['everyday' => $everyday, 'wallet' => $wallet][$filters['account']]->id : null,
        'category' => isset($filters['category']) ? ['groceries' => $groceries, 'health' => $health][$filters['category']]->id : null,
    ];

    $response = $this->actingAs($user)->get(route('expenses.index', array_filter($query)));

    $response->assertOk()->assertSee('Spent in total '.$scope);

    foreach ($visible as $concept) {
        $response->assertSee($concept);
    }
    foreach ($hidden as $concept) {
        $response->assertDontSee($concept);
    }
})->with([
    'by account' => [['account' => 'everyday'], ['Everyday groceries', 'Everyday pharmacy'], ['Wallet groceries'], 'from Everyday'],
    'by category' => [['category' => 'groceries'], ['Everyday groceries', 'Wallet groceries'], ['Everyday pharmacy'], 'on Groceries'],
    'by both' => [['account' => 'everyday', 'category' => 'groceries'], ['Everyday groceries'], ['Everyday pharmacy', 'Wallet groceries'], 'from Everyday on Groceries'],
]);

test('an account or a category that belongs to somebody else is ignored as a filter', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create();
    $category = Category::factory()->for($user)->create();
    expenseFor($account, $category, ['concept' => 'My expense', 'date' => '2026-11-02']);
    $foreignAccount = Account::factory()->create();
    $foreignCategory = Category::factory()->create();
    expenseFor($foreignAccount, $foreignCategory, ['concept' => 'Foreign expense', 'date' => '2026-11-02']);

    $this->actingAs($user)
        ->get(route('expenses.index', ['account' => $foreignAccount->id, 'category' => $foreignCategory->id]))
        ->assertOk()
        ->assertSee('My expense')
        ->assertDontSee('Foreign expense')
        ->assertSee('across all accounts')
        ->assertDontSee('Clear filters');
});

test('the filters are always visible', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('expenses.index'))
        ->assertSee('name="period"', false)
        ->assertSee('name="account"', false)
        ->assertSee('name="category"', false)
        ->assertDontSee('aria-controls', false);
});

test('empty states explain why there is nothing to show', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create();

    $this->actingAs($user)
        ->get(route('expenses.index'))
        ->assertSee('No expenses yet')
        ->assertDontSee('No expenses match these filters');

    $this->actingAs($user)
        ->get(route('expenses.index', ['account' => $account->id]))
        ->assertSee('No expenses match these filters')
        ->assertDontSee('No expenses yet');
});

test('a limited period hides older expenses and offers to show all of them', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create();
    $category = Category::factory()->for($user)->create();
    expenseFor($account, $category, ['concept' => 'Recent expense', 'date' => '2026-11-02']);
    expenseFor($account, $category, ['concept' => 'Old expense', 'date' => '1999-08-20']);

    $this->actingAs($user)
        ->get(route('expenses.index', ['period' => '3m']))
        ->assertSee('Recent expense')
        ->assertDontSee('Old expense')
        ->assertSee('Show all expenses (1 more)')
        ->assertSee('period=all', false);
});

test('the expenses of an inactive account are still listed', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->inactive()->create(['name' => 'Closed account']);
    expenseFor($account, Category::factory()->for($user)->create(), ['concept' => 'Old payment', 'date' => '2026-11-02']);

    $this->actingAs($user)
        ->get(route('expenses.index'))
        ->assertSee('Old payment')
        ->assertSee('Closed account');
});

test('the navigation links to the expenses page', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertSee(route('expenses.index'), false);
});

test('a confirmation is shown after creating or updating an expense', function (string $status, string $message) {
    $this->actingAs(User::factory()->create())
        ->withSession(['status' => $status])
        ->get(route('expenses.index'))
        ->assertSee($message);
})->with([
    'created' => ['expense-created', 'Expense created.'],
    'updated' => ['expense-updated', 'Expense updated.'],
]);

test('each expense shows the symbol of its own account\'s currency and no total is shown', function () {
    $user = User::factory()->create();
    $dollars = Currency::factory()->for($user)->create(['symbol' => '$']);
    $euros = Currency::factory()->for($user)->create(['symbol' => '€']);
    $category = Category::factory()->for($user)->create();
    expenseFor(Account::factory()->for($user)->for($dollars)->create(), $category, ['amount' => 10, 'date' => '2026-11-02']);
    expenseFor(Account::factory()->for($user)->for($euros)->create(), $category, ['amount' => 20, 'date' => '2026-11-03']);

    $this->actingAs($user)
        ->get(route('expenses.index'))
        ->assertSee('−$10.00')
        ->assertSee('−€20.00')
        ->assertSee('2 expenses')
        ->assertDontSee('30.00');
});
