<?php

use App\Models\Account;
use App\Models\Category;
use App\Models\Income;
use App\Models\User;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-11-13 12:00:00'));
});

/**
 * Creates an income that belongs to the given account and category.
 *
 * @param  array<string, mixed>  $attributes
 */
function incomeFor(Account $account, Category $category, array $attributes = []): Income
{
    return Income::factory()->for($account)->for($category)->create($attributes);
}

test('guests are redirected to the login page', function () {
    $this->get(route('incomes.index'))->assertRedirect(route('login'));
});

test('the index lists only the user\'s incomes with their details', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create(['name' => 'Everyday']);
    $category = Category::factory()->create(['name' => 'Freelance']);
    incomeFor($account, $category, ['concept' => 'Logo project', 'amount' => 450, 'date' => '2026-11-10']);
    incomeFor(Account::factory()->create(), $category, ['concept' => 'Secret income', 'date' => '2026-11-10']);

    $this->actingAs($user)
        ->get(route('incomes.index'))
        ->assertOk()
        ->assertSee('Logo project')
        ->assertSee('Nov 10')
        ->assertSee('Freelance · Everyday')
        ->assertSee('+€450.00')
        ->assertDontSee('Secret income');
});

test('incomes are grouped by month with counts and totals, newest first', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create();
    $category = Category::factory()->create();
    incomeFor($account, $category, ['concept' => 'Logo project', 'amount' => 450, 'date' => '2026-11-10']);
    incomeFor($account, $category, ['concept' => 'Monthly salary', 'amount' => 3200, 'date' => '2026-11-01']);
    incomeFor($account, $category, ['concept' => 'Deposit', 'amount' => 300, 'date' => '2026-10-15']);

    $this->actingAs($user)
        ->get(route('incomes.index'))
        ->assertSeeInOrder([
            'November 2026', '2 incomes · +€3,650.00', 'Logo project', 'Monthly salary',
            'October 2026', '1 income · +€300.00', 'Deposit',
        ]);
});

test('the header sums what was received in the selected period', function (array $query, string $phrase, string $total) {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create();
    $category = Category::factory()->create();
    incomeFor($account, $category, ['amount' => 100, 'date' => '2026-11-02']);
    incomeFor($account, $category, ['amount' => 900, 'date' => '2026-10-02']);
    incomeFor($account, $category, ['amount' => 5000, 'date' => '2020-03-05']);

    $this->actingAs($user)
        ->get(route('incomes.index', $query))
        ->assertSeeInOrder([$total, 'received '.$phrase.' across all accounts']);
})->with([
    'default (all time)' => [[], 'in total', '+€6,000.00'],
    'last 3 months' => [['period' => '3m'], 'in the last 3 months', '+€1,000.00'],
    'this month' => [['period' => 'month'], 'this month', '+€100.00'],
]);

test('incomes can be filtered by account, by category and by both', function (array $filters, array $visible, array $hidden, string $scope) {
    $user = User::factory()->create();
    $everyday = Account::factory()->for($user)->create(['name' => 'Everyday']);
    $savings = Account::factory()->for($user)->create(['name' => 'Savings']);
    $salary = Category::factory()->create(['name' => 'Salary']);
    $gifts = Category::factory()->create(['name' => 'Gifts']);
    incomeFor($everyday, $salary, ['concept' => 'Everyday salary', 'date' => '2026-11-02']);
    incomeFor($everyday, $gifts, ['concept' => 'Everyday gift', 'date' => '2026-11-03']);
    incomeFor($savings, $salary, ['concept' => 'Savings salary', 'date' => '2026-11-04']);

    $query = [
        'account' => isset($filters['account']) ? ['everyday' => $everyday, 'savings' => $savings][$filters['account']]->id : null,
        'category' => isset($filters['category']) ? ['salary' => $salary, 'gifts' => $gifts][$filters['category']]->id : null,
    ];

    $response = $this->actingAs($user)->get(route('incomes.index', array_filter($query)));

    $response->assertOk()->assertSee('received in total '.$scope);

    foreach ($visible as $concept) {
        $response->assertSee($concept);
    }
    foreach ($hidden as $concept) {
        $response->assertDontSee($concept);
    }
})->with([
    'by account' => [['account' => 'everyday'], ['Everyday salary', 'Everyday gift'], ['Savings salary'], 'into Everyday'],
    'by category' => [['category' => 'salary'], ['Everyday salary', 'Savings salary'], ['Everyday gift'], 'from Salary'],
    'by both' => [['account' => 'everyday', 'category' => 'salary'], ['Everyday salary'], ['Everyday gift', 'Savings salary'], 'into Everyday from Salary'],
]);

test('an account that belongs to somebody else is ignored as a filter', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create();
    $category = Category::factory()->create();
    incomeFor($account, $category, ['concept' => 'My income', 'date' => '2026-11-02']);
    $foreignAccount = Account::factory()->create();
    incomeFor($foreignAccount, $category, ['concept' => 'Foreign income', 'date' => '2026-11-02']);

    $this->actingAs($user)
        ->get(route('incomes.index', ['account' => $foreignAccount->id]))
        ->assertOk()
        ->assertSee('My income')
        ->assertDontSee('Foreign income')
        ->assertSee('across all accounts')
        ->assertDontSee('Clear filters');
});

test('active filters can be cleared', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create();

    $this->actingAs($user)
        ->get(route('incomes.index', ['account' => $account->id]))
        ->assertSee('Clear filters');

    $this->actingAs($user)
        ->get(route('incomes.index', ['period' => '3m']))
        ->assertSee('Clear filters');

    $this->actingAs($user)
        ->get(route('incomes.index'))
        ->assertDontSee('Clear filters');
});

test('the filters are always visible', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('incomes.index'))
        ->assertSee('name="period"', false)
        ->assertSee('name="account"', false)
        ->assertSee('name="category"', false)
        ->assertDontSee('aria-controls', false);
});

test('empty states explain why there is nothing to show', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create();

    $this->actingAs($user)
        ->get(route('incomes.index'))
        ->assertSee('No incomes yet')
        ->assertDontSee('No incomes match these filters');

    $this->actingAs($user)
        ->get(route('incomes.index', ['account' => $account->id]))
        ->assertSee('No incomes match these filters')
        ->assertDontSee('No incomes yet');
});

test('all incomes are shown by default', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create();
    $category = Category::factory()->create();
    incomeFor($account, $category, ['concept' => 'Recent income', 'date' => '2026-11-02']);
    incomeFor($account, $category, ['concept' => 'Old income', 'date' => '1999-08-20']);

    $this->actingAs($user)
        ->get(route('incomes.index'))
        ->assertSee('Recent income')
        ->assertSee('Old income')
        ->assertSee('August 1999')
        ->assertDontSee('Show all incomes')
        ->assertDontSee('Clear filters');
});

test('a limited period hides older incomes and offers to show all of them', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create();
    $category = Category::factory()->create();
    incomeFor($account, $category, ['concept' => 'Recent income', 'date' => '2026-11-02']);
    incomeFor($account, $category, ['concept' => 'Old income', 'date' => '1999-08-20']);

    $this->actingAs($user)
        ->get(route('incomes.index', ['period' => '3m']))
        ->assertSee('Recent income')
        ->assertDontSee('Old income')
        ->assertSee('Show all incomes (1 more)')
        ->assertSee('period=all', false);
});

test('each period preset limits the incomes shown', function (string $period, array $visible, array $hidden) {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create();
    $category = Category::factory()->create();
    incomeFor($account, $category, ['concept' => 'This month income', 'date' => '2026-11-02']);
    incomeFor($account, $category, ['concept' => 'Two months ago income', 'date' => '2026-09-15']);
    incomeFor($account, $category, ['concept' => 'Five months ago income', 'date' => '2026-06-15']);
    incomeFor($account, $category, ['concept' => 'Last year income', 'date' => '2025-12-31']);

    $response = $this->actingAs($user)->get(route('incomes.index', ['period' => $period]));

    foreach ($visible as $concept) {
        $response->assertSee($concept);
    }
    foreach ($hidden as $concept) {
        $response->assertDontSee($concept);
    }
})->with([
    'this month' => ['month', ['This month income'], ['Two months ago income', 'Five months ago income', 'Last year income']],
    'last 3 months' => ['3m', ['This month income', 'Two months ago income'], ['Five months ago income', 'Last year income']],
    'last 6 months' => ['6m', ['This month income', 'Two months ago income', 'Five months ago income'], ['Last year income']],
    'this year' => ['year', ['This month income', 'Two months ago income', 'Five months ago income'], ['Last year income']],
    'all time' => ['all', ['This month income', 'Two months ago income', 'Five months ago income', 'Last year income'], []],
]);

test('a custom range includes both end dates and is shown in the header', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create();
    $category = Category::factory()->create();
    incomeFor($account, $category, ['concept' => 'Before range', 'date' => '2026-02-28']);
    incomeFor($account, $category, ['concept' => 'First day', 'date' => '2026-03-01']);
    incomeFor($account, $category, ['concept' => 'Last day', 'date' => '2026-03-31']);
    incomeFor($account, $category, ['concept' => 'After range', 'date' => '2026-04-01']);

    $this->actingAs($user)
        ->get(route('incomes.index', ['period' => 'custom', 'from' => '2026-03-01', 'to' => '2026-03-31']))
        ->assertSee('First day')
        ->assertSee('Last day')
        ->assertDontSee('Before range')
        ->assertDontSee('After range')
        ->assertSee('received between Mar 1, 2026 and Mar 31, 2026');
});

test('a custom range can be open on one side or given in the wrong order', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create();
    $category = Category::factory()->create();
    incomeFor($account, $category, ['concept' => 'Early income', 'date' => '2026-01-10']);
    incomeFor($account, $category, ['concept' => 'Late income', 'date' => '2026-06-10']);

    $this->actingAs($user)
        ->get(route('incomes.index', ['period' => 'custom', 'from' => '2026-03-01']))
        ->assertSee('Late income')
        ->assertDontSee('Early income')
        ->assertSee('received since Mar 1, 2026');

    $this->actingAs($user)
        ->get(route('incomes.index', ['period' => 'custom', 'from' => '2026-12-31', 'to' => '2026-01-01']))
        ->assertSee('Early income')
        ->assertSee('Late income');
});

test('an unknown period or an invalid date falls back to the defaults', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create();
    incomeFor($account, Category::factory()->create(), ['concept' => 'Recent income', 'date' => '2026-11-02']);

    $this->actingAs($user)
        ->get(route('incomes.index', ['period' => 'forever']))
        ->assertOk()
        ->assertSee('Recent income')
        ->assertSee('received in total')
        ->assertDontSee('Clear filters');

    $this->actingAs($user)
        ->get(route('incomes.index', ['period' => 'custom', 'from' => '2026-13-45', 'to' => 'tomorrow']))
        ->assertOk()
        ->assertSee('Recent income')
        ->assertSee('received in total');
});

test('a period without incomes points to the ones outside of it', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create();
    incomeFor($account, Category::factory()->create(), ['concept' => 'Old income', 'date' => '2021-05-05']);

    $this->actingAs($user)
        ->get(route('incomes.index', ['period' => '3m']))
        ->assertSee('No incomes in this period')
        ->assertSee('There is 1 income outside it.')
        ->assertSee('Show all incomes')
        ->assertDontSee('No incomes yet')
        ->assertDontSee('Old income');
});
