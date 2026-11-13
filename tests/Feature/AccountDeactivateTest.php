<?php

use App\Models\Account;
use App\Models\Category;
use App\Models\Expense;
use App\Models\Income;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $account = Account::factory()->create();

    $this->delete(route('accounts.destroy', $account))->assertRedirect(route('login'));

    $this->assertNotSoftDeleted($account);
});

test('the owner deactivates an account and is told about it', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create(['name' => 'Everyday']);

    $this->actingAs($user)
        ->delete(route('accounts.destroy', $account))
        ->assertRedirect(route('accounts.index'))
        ->assertSessionHas('status', 'account-deactivated');

    $this->assertSoftDeleted($account);

    $this->actingAs($user)
        ->get(route('accounts.index'))
        ->assertSee('Account deactivated.')
        ->assertDontSee('Everyday');
});

test('a deactivated account leaves the dashboard and the totals', function () {
    $user = User::factory()->create();
    $kept = Account::factory()->for($user)->create(['name' => 'Savings', 'balance' => 100]);
    $deactivated = Account::factory()->for($user)->create(['name' => 'Old wallet', 'balance' => 50]);

    $this->actingAs($user)->delete(route('accounts.destroy', $deactivated));

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertSee('Savings')
        ->assertDontSee('Old wallet')
        ->assertSee('across 1 account')
        ->assertSee('€100.00');

    $this->actingAs($user)
        ->get(route('accounts.index'))
        ->assertSee('Savings')
        ->assertDontSee('Old wallet')
        ->assertSee('across 1 account');
});

test('the history of a deactivated account is kept', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create(['name' => 'Old wallet']);
    $category = Category::factory()->create();
    $income = Income::factory()->for($account)->for($category)->create(['concept' => 'Birthday gift', 'date' => '2026-11-02']);
    $expense = Expense::factory()->for($account)->for($category)->create();

    $this->actingAs($user)->delete(route('accounts.destroy', $account))->assertRedirect();

    $this->assertModelExists($income);
    $this->assertModelExists($expense);

    $this->actingAs($user)
        ->get(route('incomes.index'))
        ->assertSee('Birthday gift')
        ->assertSee('Old wallet')
        ->assertSee('Old wallet (inactive)');
});

test('incomes can still be filtered by a deactivated account', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create();
    $other = Account::factory()->for($user)->create();
    $category = Category::factory()->create();
    Income::factory()->for($account)->for($category)->create(['concept' => 'Old income', 'date' => '2026-11-02']);
    Income::factory()->for($other)->for($category)->create(['concept' => 'Current income', 'date' => '2026-11-03']);
    $account->delete();

    $this->actingAs($user)
        ->get(route('incomes.index', ['account' => $account->id]))
        ->assertSee('Old income')
        ->assertDontSee('Current income');
});

test('another user\'s account cannot be deactivated', function () {
    $account = Account::factory()->create();

    $this->actingAs(User::factory()->create())
        ->delete(route('accounts.destroy', $account))
        ->assertNotFound();

    $this->assertNotSoftDeleted($account);
});

test('an account cannot be deactivated twice', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create();

    $this->actingAs($user)->delete(route('accounts.destroy', $account))->assertRedirect();
    $this->actingAs($user)->delete(route('accounts.destroy', $account))->assertNotFound();
});

test('a deactivated account can no longer be edited', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->inactive()->create();

    $this->actingAs($user)->get(route('accounts.edit', $account))->assertNotFound();

    $this->actingAs($user)
        ->patch(route('accounts.update', $account), ['name' => 'Renamed', 'bank' => 'Bank'])
        ->assertNotFound();

    expect(Account::withTrashed()->find($account->id)->name)->not->toBe('Renamed');
});
