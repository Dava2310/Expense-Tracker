<?php

use App\Models\Account;
use App\Models\Category;
use App\Models\Income;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $income = Income::factory()->create();

    $this->get(route('incomes.edit', $income))->assertRedirect(route('login'));
    $this->patch(route('incomes.update', $income), [])->assertRedirect(route('login'));
});

test('the owner sees the income with an editable concept and its locked details', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create(['name' => 'Everyday', 'bank' => 'Harbor Bank']);
    $income = Income::factory()->for($account)->for(Category::factory()->create(['name' => 'Salary']))->create([
        'amount' => 3200,
        'concept' => 'Monthly salary',
        'date' => '2026-11-01',
    ]);

    $this->actingAs($user)
        ->get(route('incomes.edit', $income))
        ->assertOk()
        ->assertSee('+€3,200.00')
        ->assertSee('Added to Everyday on Sunday, Nov 1, 2026')
        ->assertSee('value="Monthly salary"', false)
        ->assertSee('name="_method" value="PATCH"', false)
        ->assertSee('Salary')
        ->assertSee('Everyday · Harbor Bank')
        ->assertDontSee('name="amount"', false)
        ->assertDontSee('name="date"', false)
        ->assertDontSee('name="account_id"', false)
        ->assertDontSee('name="category_id"', false);
});

test('the owner updates the concept', function () {
    $user = User::factory()->create();
    $income = Income::factory()->for(Account::factory()->for($user))->create(['concept' => 'Old concept']);

    $this->actingAs($user)
        ->patch(route('incomes.update', $income), ['concept' => 'New concept'])
        ->assertRedirect(route('incomes.index'))
        ->assertSessionHas('status', 'income-updated');

    expect($income->refresh()->concept)->toBe('New concept');
});

test('submitted amount, date, account and category are ignored', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create(['balance' => 100]);
    $category = Category::factory()->create();
    $income = Income::factory()->for($account)->for($category)->create([
        'amount' => 50,
        'date' => '2026-11-01',
    ]);

    $this->actingAs($user)->patch(route('incomes.update', $income), [
        'concept' => 'New concept',
        'amount' => '999',
        'date' => '2020-01-01',
        'account_id' => Account::factory()->for($user)->create()->id,
        'category_id' => Category::factory()->create()->id,
    ]);

    $income->refresh();

    expect($income->concept)->toBe('New concept')
        ->and($income->amount)->toEqual(50)
        ->and($income->date->toDateString())->toBe('2026-11-01')
        ->and($income->account_id)->toBe($account->id)
        ->and($income->category_id)->toBe($category->id)
        ->and($account->refresh()->balance)->toEqual(100);
});

test('another user cannot see or update the income', function () {
    $income = Income::factory()->create(['concept' => 'Original']);
    $intruder = User::factory()->create();

    $this->actingAs($intruder)
        ->get(route('incomes.edit', $income))
        ->assertNotFound();

    $this->actingAs($intruder)
        ->patch(route('incomes.update', $income), ['concept' => 'Hacked'])
        ->assertNotFound();

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
    $income = Income::factory()->for(Account::factory()->for($user)->inactive())->create(['concept' => 'Old concept']);

    $this->actingAs($user)
        ->patch(route('incomes.update', $income), ['concept' => 'New concept'])
        ->assertRedirect(route('incomes.index'));

    expect($income->refresh()->concept)->toBe('New concept');
});
