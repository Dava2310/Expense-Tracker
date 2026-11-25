<?php

use App\Models\Account;
use App\Models\Category;
use App\Models\Expense;
use App\Models\User;

/**
 * A valid update for the given expense (its own values), which each test can override.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function validExpenseChange(Expense $expense, array $overrides = []): array
{
    return [
        'amount' => number_format($expense->amount, 2, '.', ''),
        'concept' => $expense->concept,
        'date' => $expense->date->format('Y-m-d'),
        'account_id' => $expense->account_id,
        'category_id' => $expense->category_id,
        ...$overrides,
    ];
}

test('guests are redirected to the login page', function () {
    $expense = Expense::factory()->create();

    $this->get(route('expenses.edit', $expense))->assertRedirect(route('login'));
    $this->patch(route('expenses.update', $expense), [])->assertRedirect(route('login'));
});

test('the owner sees the expense with every field filled in', function () {
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
        ->assertSee('Edit expense')
        ->assertSee('value="84.30"', false)
        ->assertSee('value="Weekly groceries"', false)
        ->assertSee('value="2026-11-13"', false)
        ->assertSee('name="_method" value="PATCH"', false)
        ->assertSee('name="account_id"', false)
        ->assertSee('name="category_id"', false)
        ->assertSee('Everyday')
        ->assertSee('Groceries')
        ->assertSee('Save changes');
});

test('the owner updates the concept', function () {
    $user = User::factory()->create();
    $expense = Expense::factory()->for(Account::factory()->for($user))->for(Category::factory()->for($user))->create(['concept' => 'Old concept']);

    $this->actingAs($user)
        ->patch(route('expenses.update', $expense), validExpenseChange($expense, ['concept' => 'New concept']))
        ->assertRedirect(route('expenses.index'))
        ->assertSessionHas('status', 'expense-updated');

    expect($expense->refresh()->concept)->toBe('New concept');
});

test('the amount, date, account and category are saved and the balances follow', function () {
    $user = User::factory()->create();
    $checking = Account::factory()->for($user)->create(['balance' => 100]);
    $card = Account::factory()->for($user)->create(['balance' => 100]);
    $oldCategory = Category::factory()->for($user)->create();
    $newCategory = Category::factory()->for($user)->create();
    $expense = Expense::factory()->for($checking)->for($oldCategory)->create(['amount' => 50, 'date' => '2026-11-01']);

    $this->actingAs($user)->patch(route('expenses.update', $expense), [
        'concept' => 'New concept',
        'amount' => '45',
        'date' => '2026-12-02',
        'account_id' => $card->id,
        'category_id' => $newCategory->id,
    ])->assertRedirect(route('expenses.index'));

    $expense->refresh();

    expect($expense->concept)->toBe('New concept')
        ->and($expense->amount)->toEqual(45)
        ->and($expense->date->toDateString())->toBe('2026-12-02')
        ->and($expense->account_id)->toBe($card->id)
        ->and($expense->category_id)->toBe($newCategory->id)
        ->and($checking->refresh()->balance)->toEqual(150)
        ->and($card->refresh()->balance)->toEqual(55);
});

test('another user cannot see or update the expense', function () {
    $expense = Expense::factory()->create(['concept' => 'Original']);
    $intruder = User::factory()->create();
    $intruderAccount = Account::factory()->for($intruder)->create(['balance' => 1000]);
    $intruderCategory = Category::factory()->for($intruder)->create();

    $this->actingAs($intruder)
        ->get(route('expenses.edit', $expense))
        ->assertForbidden();

    $this->actingAs($intruder)
        ->patch(route('expenses.update', $expense), [
            'amount' => '10',
            'concept' => 'Hacked',
            'date' => '2026-11-01',
            'account_id' => $intruderAccount->id,
            'category_id' => $intruderCategory->id,
        ])
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
    $expense = Expense::factory()->for(Account::factory()->for($user)->inactive())->for(Category::factory()->for($user))->create(['concept' => 'Old concept']);

    $this->actingAs($user)
        ->patch(route('expenses.update', $expense), validExpenseChange($expense, ['concept' => 'New concept']))
        ->assertRedirect(route('expenses.index'));

    expect($expense->refresh()->concept)->toBe('New concept');
});
