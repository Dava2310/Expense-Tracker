<?php

use App\Models\Account;
use App\Models\Category;
use App\Models\Expense;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $expense = Expense::factory()->create();

    $this->get(route('expenses.edit', $expense))->assertRedirect(route('login'));
    $this->patch(route('expenses.update', $expense), [])->assertRedirect(route('login'));
});

test('the owner sees the expense with an editable concept and its locked details', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create(['name' => 'Everyday', 'bank' => 'Harbor Bank']);
    $expense = Expense::factory()->for($account)->for(Category::factory()->for($user)->create(['name' => 'Groceries']))->create([
        'amount' => 84.30,
        'concept' => 'Weekly groceries',
        'date' => '2026-11-13',
    ]);

    $this->actingAs($user)
        ->get(route('expenses.edit', $expense))
        ->assertOk()
        ->assertSee('−€84.30')
        ->assertSee('Deducted from Everyday on Friday, Nov 13, 2026')
        ->assertSee('value="Weekly groceries"', false)
        ->assertSee('name="_method" value="PATCH"', false)
        ->assertSee('Groceries')
        ->assertSee('Everyday · Harbor Bank')
        ->assertDontSee('name="amount"', false)
        ->assertDontSee('name="date"', false)
        ->assertDontSee('name="account_id"', false)
        ->assertDontSee('name="category_id"', false);
});

test('the owner updates the concept', function () {
    $user = User::factory()->create();
    $expense = Expense::factory()->for(Account::factory()->for($user))->create(['concept' => 'Old concept']);

    $this->actingAs($user)
        ->patch(route('expenses.update', $expense), ['concept' => 'New concept'])
        ->assertRedirect(route('expenses.index'))
        ->assertSessionHas('status', 'expense-updated');

    expect($expense->refresh()->concept)->toBe('New concept');
});

test('submitted amount, date, account and category are ignored', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create(['balance' => 100]);
    $category = Category::factory()->for($user)->create();
    $expense = Expense::factory()->for($account)->for($category)->create([
        'amount' => 50,
        'date' => '2026-11-01',
    ]);

    $this->actingAs($user)->patch(route('expenses.update', $expense), [
        'concept' => 'New concept',
        'amount' => '999',
        'date' => '2020-01-01',
        'account_id' => Account::factory()->for($user)->create()->id,
        'category_id' => Category::factory()->for($user)->create()->id,
    ]);

    $expense->refresh();

    expect($expense->concept)->toBe('New concept')
        ->and($expense->amount)->toEqual(50)
        ->and($expense->date->toDateString())->toBe('2026-11-01')
        ->and($expense->account_id)->toBe($account->id)
        ->and($expense->category_id)->toBe($category->id)
        ->and($account->refresh()->balance)->toEqual(100);
});

test('another user cannot see or update the expense', function () {
    $expense = Expense::factory()->create(['concept' => 'Original']);
    $intruder = User::factory()->create();

    $this->actingAs($intruder)
        ->get(route('expenses.edit', $expense))
        ->assertForbidden();

    $this->actingAs($intruder)
        ->patch(route('expenses.update', $expense), ['concept' => 'Hacked'])
        ->assertForbidden();

    expect($expense->refresh()->concept)->toBe('Original');
});

test('an invalid concept is rejected, the expense is unchanged and the errors are shown', function (string $concept) {
    $user = User::factory()->create();
    $expense = Expense::factory()->for(Account::factory()->for($user))->create(['concept' => 'Original']);

    $this->actingAs($user)
        ->from(route('expenses.edit', $expense))
        ->patch(route('expenses.update', $expense), ['concept' => $concept])
        ->assertRedirect(route('expenses.edit', $expense))
        ->assertSessionHasErrors('concept');

    expect($expense->refresh()->concept)->toBe('Original');

    $this->actingAs($user)
        ->get(route('expenses.edit', $expense))
        ->assertSee('aria-invalid="true"', false);
})->with([
    'empty' => [''],
    'too long' => [str_repeat('a', 256)],
]);

test('an expense of a deactivated account can still be edited', function () {
    $user = User::factory()->create();
    $expense = Expense::factory()->for(Account::factory()->for($user)->inactive())->create(['concept' => 'Old concept']);

    $this->actingAs($user)
        ->patch(route('expenses.update', $expense), ['concept' => 'New concept'])
        ->assertRedirect(route('expenses.index'));

    expect($expense->refresh()->concept)->toBe('New concept');
});
