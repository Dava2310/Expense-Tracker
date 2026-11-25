<?php

use App\Models\Account;
use App\Models\Category;
use App\Models\Income;
use App\Models\User;

/**
 * A valid update for the given income (its own values), which each test can override.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function validIncomeChange(Income $income, array $overrides = []): array
{
    return [
        'amount' => number_format($income->amount, 2, '.', ''),
        'concept' => $income->concept,
        'date' => $income->date->format('Y-m-d'),
        'account_id' => $income->account_id,
        'category_id' => $income->category_id,
        ...$overrides,
    ];
}

test('guests are redirected to the login page', function () {
    $income = Income::factory()->create();

    $this->get(route('incomes.edit', $income))->assertRedirect(route('login'));
    $this->patch(route('incomes.update', $income), [])->assertRedirect(route('login'));
});

test('the owner sees the income with every field filled in', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create(['name' => 'Everyday', 'bank' => 'Harbor Bank']);
    $income = Income::factory()->for($account)->for(Category::factory()->for($user)->create(['name' => 'Salary']))->create([
        'amount' => 3200,
        'concept' => 'Monthly salary',
        'date' => '2026-11-01',
    ]);

    $this->actingAs($user)
        ->get(route('incomes.edit', $income))
        ->assertOk()
        ->assertSee('Edit income')
        ->assertSee('value="3200.00"', false)
        ->assertSee('value="Monthly salary"', false)
        ->assertSee('value="2026-11-01"', false)
        ->assertSee('name="_method" value="PATCH"', false)
        ->assertSee('name="account_id"', false)
        ->assertSee('name="category_id"', false)
        ->assertSee('Everyday')
        ->assertSee('Salary')
        ->assertSee('Save changes');
});

test('the owner updates the concept', function () {
    $user = User::factory()->create();
    $income = Income::factory()->for(Account::factory()->for($user))->for(Category::factory()->for($user))->create(['concept' => 'Old concept']);

    $this->actingAs($user)
        ->patch(route('incomes.update', $income), validIncomeChange($income, ['concept' => 'New concept']))
        ->assertRedirect(route('incomes.index'))
        ->assertSessionHas('status', 'income-updated');

    expect($income->refresh()->concept)->toBe('New concept');
});

test('the amount, date, account and category are saved and the balances follow', function () {
    $user = User::factory()->create();
    $checking = Account::factory()->for($user)->create(['balance' => 100]);
    $card = Account::factory()->for($user)->create(['balance' => 100]);
    $oldCategory = Category::factory()->for($user)->create();
    $newCategory = Category::factory()->for($user)->create();
    $income = Income::factory()->for($checking)->for($oldCategory)->create(['amount' => 50, 'date' => '2026-11-01']);

    $this->actingAs($user)->patch(route('incomes.update', $income), [
        'concept' => 'New concept',
        'amount' => '70',
        'date' => '2026-12-02',
        'account_id' => $card->id,
        'category_id' => $newCategory->id,
    ])->assertRedirect(route('incomes.index'));

    $income->refresh();

    expect($income->concept)->toBe('New concept')
        ->and($income->amount)->toEqual(70)
        ->and($income->date->toDateString())->toBe('2026-12-02')
        ->and($income->account_id)->toBe($card->id)
        ->and($income->category_id)->toBe($newCategory->id)
        ->and($checking->refresh()->balance)->toEqual(50)
        ->and($card->refresh()->balance)->toEqual(170);
});

test('another user cannot see or update the income', function () {
    $income = Income::factory()->create(['concept' => 'Original']);
    $intruder = User::factory()->create();
    $intruderAccount = Account::factory()->for($intruder)->create(['balance' => 1000]);
    $intruderCategory = Category::factory()->for($intruder)->create();

    $this->actingAs($intruder)
        ->get(route('incomes.edit', $income))
        ->assertForbidden();

    $this->actingAs($intruder)
        ->patch(route('incomes.update', $income), [
            'amount' => '10',
            'concept' => 'Hacked',
            'date' => '2026-11-01',
            'account_id' => $intruderAccount->id,
            'category_id' => $intruderCategory->id,
        ])
        ->assertForbidden();

    expect($income->refresh()->concept)->toBe('Original');
});

test('an invalid concept is rejected, the income is unchanged and the errors are shown', function (string $concept) {
    $user = User::factory()->create();
    $income = Income::factory()->for(Account::factory()->for($user))->create(['concept' => 'Original']);

    $this->actingAs($user)
        ->from(route('incomes.edit', $income))
        ->patch(route('incomes.update', $income), ['concept' => $concept])
        ->assertRedirect(route('incomes.edit', $income))
        ->assertSessionHasErrors('concept');

    expect($income->refresh()->concept)->toBe('Original');

    $this->actingAs($user)
        ->get(route('incomes.edit', $income))
        ->assertSee('aria-invalid="true"', false);
})->with([
    'empty' => [''],
    'too long' => [str_repeat('a', 256)],
]);

test('an income of a deactivated account can still be edited', function () {
    $user = User::factory()->create();
    $income = Income::factory()->for(Account::factory()->for($user)->inactive())->for(Category::factory()->for($user))->create(['concept' => 'Old concept']);

    $this->actingAs($user)
        ->patch(route('incomes.update', $income), validIncomeChange($income, ['concept' => 'New concept']))
        ->assertRedirect(route('incomes.index'));

    expect($income->refresh()->concept)->toBe('New concept');
});
