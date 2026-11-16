<?php

use App\Models\Category;
use App\Models\Expense;
use App\Models\Income;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $this->get(route('categories.index'))->assertRedirect(route('login'));
});

test('the page lists only the user\'s categories ordered by name with their details', function () {
    $user = User::factory()->create();
    Category::factory()->for($user)->create(['name' => 'Salary', 'description' => 'Monthly paycheck']);
    Category::factory()->for($user)->create(['name' => 'Groceries', 'description' => 'Supermarket runs']);
    Category::factory()->create(['name' => 'Somebody elses category']);

    $this->actingAs($user)
        ->get(route('categories.index'))
        ->assertOk()
        ->assertSeeInOrder(['Groceries', 'Supermarket runs', 'Salary', 'Monthly paycheck'])
        ->assertDontSee('Somebody elses category');
});

test('a category without details shows a fallback', function () {
    $user = User::factory()->create();
    Category::factory()->for($user)->create(['description' => null]);

    $this->actingAs($user)
        ->get(route('categories.index'))
        ->assertSee('No details');
});

test('used in adds the incomes and the expenses of the category', function () {
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create();
    Income::factory()->count(2)->for($category)->create();
    Expense::factory()->for($category)->create();

    $this->actingAs($user)
        ->get(route('categories.index'))
        ->assertSee('3 entries')
        ->assertDontSee('Not used yet');
});

test('used in is singular for one entry and says not used yet for none', function () {
    $user = User::factory()->create();
    Income::factory()->for(Category::factory()->for($user))->create();
    Category::factory()->for($user)->create();

    $this->actingAs($user)
        ->get(route('categories.index'))
        ->assertSee('1 entry')
        ->assertSee('Not used yet');
});

test('the subtitle shows how many categories there are', function (int $count, string $text) {
    $user = User::factory()->create();
    Category::factory()->count($count)->for($user)->create();

    $this->actingAs($user)
        ->get(route('categories.index'))
        ->assertSee($text);
})->with([
    'one' => [1, '1 category, shared by your expenses and incomes.'],
    'several' => [3, '3 categories, shared by your expenses and incomes.'],
]);

test('an empty state is shown when the user has no categories', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('categories.index'))
        ->assertSee('No categories yet');
});

test('the navigation links to the categories page', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertSee(route('categories.index'), false);
});
