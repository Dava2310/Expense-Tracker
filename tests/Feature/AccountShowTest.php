<?php

use App\Models\Account;
use App\Models\Category;
use App\Models\Expense;
use App\Models\Income;
use App\Models\User;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-11-13 12:00:00'));
});

test('guests are redirected to the login page', function () {
    $account = Account::factory()->create();

    $this->get(route('accounts.show', $account))->assertRedirect(route('login'));
});

test('the owner sees the account with its summary and actions', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create([
        'name' => 'Everyday',
        'bank' => 'Harbor Bank',
        'details' => 'Main account for bills',
        'balance' => 2480.15,
        'min_balance' => 500,
    ]);

    $this->actingAs($user)
        ->get(route('accounts.show', $account))
        ->assertOk()
        ->assertSee('Everyday')
        ->assertSee('Harbor Bank')
        ->assertSee('Main account for bills')
        ->assertSee('€2,480.15')
        ->assertSee('€500.00')
        ->assertSee('€1,980.15 above minimum')
        ->assertSee(route('accounts.edit', $account), false)
        ->assertSee('Deactivate')
        ->assertSee('Deactivate Everyday?')
        ->assertSee('action="'.route('accounts.destroy', $account).'"', false)
        ->assertSee('name="_method" value="DELETE"', false);
});

test('an account under its minimum shows how far below it is', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create(['balance' => 86.40, 'min_balance' => 100]);

    $this->actingAs($user)
        ->get(route('accounts.show', $account))
        ->assertSee('€13.60 below minimum')
        ->assertDontSee('above minimum');
});

test('this month only counts what came in and went out in the current month', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create();
    $category = Category::factory()->create();
    Income::factory()->for($account)->for($category)->create(['amount' => 3200, 'date' => '2026-11-01']);
    Income::factory()->for($account)->for($category)->create(['amount' => 450, 'date' => '2026-11-10']);
    Income::factory()->for($account)->for($category)->create(['amount' => 999, 'date' => '2026-10-31']);
    Expense::factory()->for($account)->for($category)->create(['amount' => 1452.47, 'date' => '2026-11-05']);
    Expense::factory()->for($account)->for($category)->create(['amount' => 777, 'date' => '2026-10-31']);

    $this->actingAs($user)
        ->get(route('accounts.show', $account))
        ->assertSeeInOrder(['This month', 'In', '+€3,650.00', 'Out', '−€1,452.47']);
});

test('activity lists the account\'s incomes and expenses together, newest first', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create();
    $category = Category::factory()->create(['name' => 'Groceries']);
    Income::factory()->for($account)->for($category)->create(['concept' => 'Logo project', 'amount' => 450, 'date' => '2026-11-10']);
    Expense::factory()->for($account)->for($category)->create(['concept' => 'Weekly shopping', 'amount' => 84.30, 'date' => '2026-11-12']);
    Income::factory()->for($account)->for($category)->create(['concept' => 'Monthly salary', 'amount' => 3200, 'date' => '2026-11-01']);
    Expense::factory()->for(Account::factory())->for($category)->create(['concept' => 'Other account expense']);
    Income::factory()->for(Account::factory())->for($category)->create(['concept' => 'Other account income']);

    $this->actingAs($user)
        ->get(route('accounts.show', $account))
        ->assertSeeInOrder([
            'Weekly shopping', 'Nov 12 · Groceries', '−€84.30',
            'Logo project', 'Nov 10 · Groceries', '+€450.00',
            'Monthly salary', 'Nov 1 · Groceries', '+€3,200.00',
        ])
        ->assertDontSee('Other account expense')
        ->assertDontSee('Other account income')
        ->assertSee(route('incomes.index', ['account' => $account->id]), false);
});

test('an account without movements explains that there is no activity', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create();

    $this->actingAs($user)
        ->get(route('accounts.show', $account))
        ->assertOk()
        ->assertSee('No activity yet');
});

test('the updated banner is shown after editing the account', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create();

    $this->actingAs($user)
        ->withSession(['status' => 'account-updated'])
        ->get(route('accounts.show', $account))
        ->assertSee('Account updated.');
});

test('accounts of other users, deactivated accounts and unknown accounts are not found', function (string $case) {
    $user = User::factory()->create();

    $account = match ($case) {
        'someone else\'s account' => Account::factory()->create(),
        'deactivated account' => Account::factory()->for($user)->inactive()->create(),
        default => null,
    };

    $this->actingAs($user)
        ->get(route('accounts.show', $account?->id ?? 999999))
        ->assertNotFound();
})->with(['someone else\'s account', 'deactivated account', 'unknown account']);
