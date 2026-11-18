<?php

use App\Models\Account;
use App\Models\Category;
use App\Models\Currency;
use App\Models\Expense;
use App\Models\Income;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

test('guests are redirected to the login page', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});

test('dashboard shows only the user\'s accounts with balances and minimums', function () {
    $user = User::factory()->create();
    Account::factory()->for($user)->create([
        'name' => 'Everyday', 'bank' => 'Harbor Bank', 'balance' => 2480.15, 'min_balance' => 500,
    ]);
    Account::factory()->for($user)->create([
        'name' => 'Savings', 'bank' => 'Harbor Bank', 'balance' => 12350, 'min_balance' => 5000,
    ]);
    Account::factory()->create(['name' => 'Someone else\'s account']);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Everyday')
        ->assertSee('€2,480.15')
        ->assertSee('Minimum €500.00')
        ->assertSee('Savings')
        ->assertSee('€12,350.00')
        ->assertSee('2 accounts')
        ->assertDontSee('Total balance')
        ->assertDontSee('Someone else\'s account');
});

test('each account card shows the symbol of its own currency', function () {
    $user = User::factory()->create();
    $dollars = Currency::factory()->for($user)->create(['symbol' => '$']);
    $pesos = Currency::factory()->for($user)->create(['symbol' => 'COP ']);
    Account::factory()->for($user)->for($dollars)->create(['balance' => 100, 'min_balance' => 10]);
    Account::factory()->for($user)->for($pesos)->create(['balance' => 2000, 'min_balance' => 500]);
    $old = Account::factory()->for($user)->for($dollars)->create(['balance' => 7]);
    $old->delete();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertSee('$100.00')
        ->assertSee('Minimum $10.00')
        ->assertSee('COP 2,000.00')
        ->assertSee('Minimum COP 500.00')
        ->assertSee('$7.00');
});

test('each account card links to the account page', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertSee('href="'.route('accounts.show', $account).'"', false);
});

test('an account under its minimum is flagged', function () {
    $user = User::factory()->create();
    Account::factory()->for($user)->create([
        'name' => 'Wallet', 'balance' => 86.40, 'min_balance' => 100,
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Below €100.00 minimum')
        ->assertDontSee('Minimum €100.00');
});

test('an account exactly at its minimum is not flagged', function () {
    $user = User::factory()->create();
    Account::factory()->for($user)->create(['balance' => 100, 'min_balance' => 100]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertDontSee('Below €100.00 minimum')
        ->assertSee('Minimum €100.00');
});

test('dashboard invites users without accounts to create one', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('No accounts yet')
        ->assertSee(route('accounts.create'), false);
});

test('navigation links to every module and offers profile and log out', function () {
    $user = User::factory()->create(['name' => 'Alex Rivera', 'email' => 'alex@example.com']);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('aria-current="page"', false)
        ->assertSee(route('accounts.index'), false)
        ->assertSee(route('expenses.index'), false)
        ->assertSee(route('incomes.index'), false)
        ->assertSee(route('categories.index'), false)
        ->assertSee(route('profile.edit'), false)
        ->assertSee('action="'.route('logout').'"', false)
        ->assertSee('Alex Rivera')
        ->assertSee('alex@example.com')
        ->assertSeeInOrder(['Expenses', 'Incomes', 'Categories'])
        ->assertDontSee('aria-disabled="true"', false);
});

describe('monthly overview', function () {
    beforeEach(function () {
        $this->travelTo(Carbon::parse('2026-11-13 12:00:00'));
        $this->user = User::factory()->create();
        $this->account = Account::factory()->for($this->user)->create();
        $this->category = Category::factory()->for($this->user)->create(['name' => 'Groceries']);
    });

    test('the chart totals incomes and expenses of each of the last six months', function () {
        Income::factory()->create(['account_id' => $this->account->id, 'category_id' => $this->category->id, 'amount' => 3000, 'date' => '2026-11-01']);
        Income::factory()->create(['account_id' => $this->account->id, 'category_id' => $this->category->id, 'amount' => 650, 'date' => '2026-11-30']);
        Expense::factory()->create(['account_id' => $this->account->id, 'category_id' => $this->category->id, 'amount' => 1478.87, 'date' => '2026-11-10']);
        Expense::factory()->create(['account_id' => $this->account->id, 'category_id' => $this->category->id, 'amount' => 2310.45, 'date' => '2026-06-15']);
        Expense::factory()->create(['account_id' => $this->account->id, 'category_id' => $this->category->id, 'amount' => 999.99, 'date' => '2026-05-31']);

        $this->actingAs($this->user)
            ->get(route('dashboard', ['inc_exp' => $this->account->id]))
            ->assertOk()
            ->assertSee('Income vs. expenses')
            ->assertSee('November 2026: income €3,650.00, expenses €1,478.87')
            ->assertSee('June 2026: income €0.00, expenses €2,310.45')
            ->assertDontSee('May 2026')
            ->assertDontSee('999.99');
    });

    test('the chart ignores the incomes and expenses of other users', function () {
        $stranger = Account::factory()->create();
        Expense::factory()->create(['account_id' => $stranger->id, 'category_id' => Category::factory()->for($stranger->user)->create()->id, 'amount' => 777.77, 'date' => '2026-11-05']);

        $this->actingAs($this->user)
            ->get(route('dashboard', ['inc_exp' => $this->account->id]))
            ->assertOk()
            ->assertSee('No incomes or expenses in this period yet.')
            ->assertDontSee('777.77');
    });

    test('the chart asks for an account and runs no income, expense or category query until one is chosen', function () {
        Income::factory()->create(['account_id' => $this->account->id, 'category_id' => $this->category->id, 'amount' => 3000, 'date' => '2026-11-01']);

        DB::enableQueryLog();

        $this->actingAs($this->user)
            ->get(route('dashboard'))
            ->assertSee('Choose an account to see its income and expenses.')
            ->assertDontSee('3,000');

        $queries = collect(DB::getQueryLog())->pluck('query')
            ->filter(fn (string $query): bool => str_contains($query, 'incomes') || str_contains($query, 'expenses') || str_contains($query, 'categories'));

        expect($queries)->toBeEmpty();
    });

    test('the chart only looks at the chosen account and uses its currency', function () {
        $dollars = Currency::factory()->for($this->user)->create(['symbol' => '$']);
        $other = Account::factory()->for($this->user)->for($dollars)->create(['name' => 'Travel card']);
        Income::factory()->create(['account_id' => $other->id, 'category_id' => $this->category->id, 'amount' => 500, 'date' => '2026-11-01']);
        Income::factory()->create(['account_id' => $this->account->id, 'category_id' => $this->category->id, 'amount' => 3000, 'date' => '2026-11-01']);

        $this->actingAs($this->user)
            ->get(route('dashboard', ['inc_exp' => $other->id]))
            ->assertSee('Last 6 months · Travel card')
            ->assertSee('November 2026: income $500.00, expenses $0.00')
            ->assertDontSee('3,000');
    });

    test('a deactivated account, one of somebody else or an invalid id is ignored by the chart filter', function () {
        $inactive = Account::factory()->for($this->user)->create();
        $inactive->delete();
        $stranger = Account::factory()->create();

        foreach ([$inactive->id, $stranger->id, 'abc'] as $id) {
            $this->actingAs($this->user)
                ->get(route('dashboard', ['inc_exp' => $id]))
                ->assertSee('Choose an account to see its income and expenses.');
        }
    });

    test('both filters can be used at once and each keeps the other\'s account', function () {
        $other = Account::factory()->for($this->user)->create(['name' => 'Travel card']);

        $this->actingAs($this->user)
            ->get(route('dashboard', ['inc_exp' => $this->account->id, 'cat' => $other->id]))
            ->assertSee('name="cat" value="'.$other->id.'"', false)
            ->assertSee('name="inc_exp" value="'.$this->account->id.'"', false);
    });

    test('spending by category lists this month\'s expenses, biggest first', function () {
        $rent = Category::factory()->for($this->user)->create(['name' => 'Rent']);
        Expense::factory()->create(['account_id' => $this->account->id, 'category_id' => $this->category->id, 'amount' => 84.30, 'date' => '2026-11-02']);
        Expense::factory()->create(['account_id' => $this->account->id, 'category_id' => $this->category->id, 'amount' => 15.70, 'date' => '2026-11-09']);
        Expense::factory()->create(['account_id' => $this->account->id, 'category_id' => $rent->id, 'amount' => 1250, 'date' => '2026-11-01']);
        Expense::factory()->create(['account_id' => $this->account->id, 'category_id' => Category::factory()->for($this->user)->create(['name' => 'Travel'])->id, 'amount' => 500, 'date' => '2026-10-31']);

        $this->actingAs($this->user)
            ->get(route('dashboard', ['cat' => $this->account->id]))
            ->assertSeeInOrder(['Spending by category', 'November so far · €1,350.00 total', 'Rent', '€1,250.00', 'Groceries', '€100.00'])
            ->assertDontSee('Travel')
            ->assertSee('href="'.route('expenses.index', ['account' => $this->account->id]).'"', false);
    });

    test('spending by category shows only the top seven categories but totals them all', function () {
        foreach (range(1, 8) as $number) {
            Expense::factory()->create([
                'account_id' => $this->account->id,
                'category_id' => Category::factory()->for($this->user)->create(['name' => "Category {$number}"])->id,
                'amount' => $number * 10,
                'date' => '2026-11-03',
            ]);
        }

        $this->actingAs($this->user)
            ->get(route('dashboard', ['cat' => $this->account->id]))
            ->assertSee('Category 8')
            ->assertSee('Category 2')
            ->assertDontSee('Category 1<', false)
            ->assertSee('€360.00 total');
    });

    test('spending by category invites to log expenses when there are none this month', function () {
        $this->actingAs($this->user)
            ->get(route('dashboard', ['cat' => $this->account->id]))
            ->assertSee('No expenses this month yet.');
    });

    test('spending by category asks for an account and loads no category data until one is chosen', function () {
        Expense::factory()->create(['account_id' => $this->account->id, 'category_id' => $this->category->id, 'amount' => 84.30, 'date' => '2026-11-02']);

        DB::enableQueryLog();

        $this->actingAs($this->user)
            ->get(route('dashboard'))
            ->assertSee('Choose an account to see its spending this month.')
            ->assertDontSee('Groceries');

        $categoryQueries = collect(DB::getQueryLog())->pluck('query')->filter(fn (string $query): bool => str_contains($query, 'categories'));

        expect($categoryQueries)->toBeEmpty();
    });

    test('spending by category only looks at the chosen account and uses its currency', function () {
        $dollars = Currency::factory()->for($this->user)->create(['symbol' => '$']);
        $other = Account::factory()->for($this->user)->for($dollars)->create(['name' => 'Travel card']);
        Expense::factory()->create(['account_id' => $this->account->id, 'category_id' => $this->category->id, 'amount' => 84.30, 'date' => '2026-11-02']);
        Expense::factory()->create(['account_id' => $other->id, 'category_id' => $this->category->id, 'amount' => 40, 'date' => '2026-11-03']);

        $this->actingAs($this->user)
            ->get(route('dashboard', ['cat' => $other->id]))
            ->assertSee('November so far · $40.00 total')
            ->assertDontSee('November so far · €');
    });

    test('an account of somebody else or an inactive one is ignored by the category filter', function () {
        $stranger = Account::factory()->create();
        $inactive = Account::factory()->for($this->user)->create();
        $inactive->delete();

        foreach ([$stranger->id, $inactive->id, 'abc'] as $id) {
            $this->actingAs($this->user)
                ->get(route('dashboard', ['cat' => $id]))
                ->assertSee('Choose an account to see its spending this month.');
        }
    });

    test('the category filter keeps the other chart\'s account in the form', function () {
        $this->actingAs($this->user)
            ->get(route('dashboard', ['cat' => $this->account->id, 'inc_exp' => $this->account->id]))
            ->assertSee('name="inc_exp" value="'.$this->account->id.'"', false)
            ->assertSee('<option value="'.$this->account->id.'" selected>', false);
    });
});

describe('inactive accounts', function () {
    test('deactivated accounts are listed apart and left out of the account count', function () {
        $user = User::factory()->create();
        Account::factory()->for($user)->create(['name' => 'Everyday', 'balance' => 100]);
        $old = Account::factory()->for($user)->create(['name' => 'Joint account', 'bank' => 'Pine Federal', 'balance' => 0]);
        $old->delete();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertSeeInOrder(['Inactive', 'Joint account', 'Pine Federal', 'Deactivated '.$old->deleted_at->format('M j, Y')])
            ->assertSee('1 account')
            ->assertDontSee('2 accounts');
    });

    test('the inactive section is hidden when no account was deactivated', function () {
        $user = User::factory()->create();
        Account::factory()->for($user)->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertDontSee('Inactive');
    });
});
