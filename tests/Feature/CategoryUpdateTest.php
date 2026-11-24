<?php

use App\Models\Category;
use App\Models\Expense;
use App\Models\Income;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $category = Category::factory()->create();

    $this->get(route('categories.edit', $category))->assertRedirect(route('login'));
    $this->patch(route('categories.update', $category), [])->assertRedirect(route('login'));
});

test('the owner sees the form with the current values and how many entries use the category', function () {
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create(['name' => 'Groceries', 'description' => 'Supermarket runs']);
    Income::factory()->for($category)->create();
    Expense::factory()->count(2)->for($category)->create();

    $this->actingAs($user)
        ->get(route('categories.edit', $category))
        ->assertOk()
        ->assertSee('Edit category')
        ->assertSee('value="Groceries"', false)
        ->assertSee('Supermarket runs')
        ->assertSee('name="_method" value="PATCH"', false)
        ->assertSee('Used by 3 entries.')
        ->assertSee('Save changes');
});

test('a category that is not used says so', function () {
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create();

    $this->actingAs($user)
        ->get(route('categories.edit', $category))
        ->assertSee('Not used by any entries yet.');
});

test('the owner updates the name and the details', function () {
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create();

    $this->actingAs($user)
        ->patch(route('categories.update', $category), ['name' => 'Pets', 'description' => 'Food and vet visits'])
        ->assertRedirect(route('categories.index'))
        ->assertSessionHas('status', 'category-updated');

    $category->refresh();

    expect($category->name)->toBe('Pets')
        ->and($category->description)->toBe('Food and vet visits');
});

test('the details can be cleared', function () {
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create(['description' => 'Old details']);

    $this->actingAs($user)
        ->patch(route('categories.update', $category), ['name' => $category->name, 'description' => ''])
        ->assertSessionHasNoErrors();

    expect($category->refresh()->description)->toBeNull();
});

test('a submitted user is ignored when updating a category', function () {
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create();

    $this->actingAs($user)->patch(route('categories.update', $category), [
        'name' => 'Pets',
        'user_id' => User::factory()->create()->id,
    ]);

    expect($category->refresh()->user_id)->toBe($user->id);
});

test('another user cannot see or update the category', function () {
    $category = Category::factory()->create(['name' => 'Original']);
    $intruder = User::factory()->create();

    $this->actingAs($intruder)
        ->get(route('categories.edit', $category))
        ->assertForbidden();

    $this->actingAs($intruder)
        ->patch(route('categories.update', $category), ['name' => 'Hacked'])
        ->assertForbidden();

    expect($category->refresh()->name)->toBe('Original');
});

test('invalid input is rejected, the category is unchanged and the errors are shown', function (array $input, string $invalid) {
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create(['name' => 'Original']);

    $this->actingAs($user)
        ->from(route('categories.edit', $category))
        ->patch(route('categories.update', $category), $input)
        ->assertRedirect(route('categories.edit', $category))
        ->assertSessionHasErrors($invalid);

    expect($category->refresh()->name)->toBe('Original');

    $this->actingAs($user)
        ->get(route('categories.edit', $category))
        ->assertSee('aria-invalid="true"', false);
})->with([
    'missing name' => [['name' => ''], 'name'],
    'name too long' => [['name' => str_repeat('a', 256)], 'name'],
    'details too long' => [['name' => 'Pets', 'description' => str_repeat('a', 256)], 'description'],
]);

test('the name of another category of the user is rejected, whatever the letter case', function () {
    $user = User::factory()->create();
    Category::factory()->for($user)->create(['name' => 'Groceries']);
    $category = Category::factory()->for($user)->create(['name' => 'Rent']);

    $this->actingAs($user)
        ->patch(route('categories.update', $category), ['name' => 'groceries'])
        ->assertSessionHasErrors(['name' => 'You already have a category called groceries.']);

    expect($category->refresh()->name)->toBe('Rent');
});

test('renaming a category to exactly the name of another one is rejected with the validation message', function () {
    $user = User::factory()->create();
    Category::factory()->for($user)->create(['name' => 'Groceries']);
    $category = Category::factory()->for($user)->create(['name' => 'Rent']);

    $this->actingAs($user)
        ->patch(route('categories.update', $category), ['name' => 'Groceries'])
        ->assertSessionHasErrors(['name' => 'You already have a category called Groceries.']);

    expect($category->refresh()->name)->toBe('Rent');
});

test('a category can be saved with its name unchanged', function () {
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create(['name' => 'Groceries', 'description' => 'Old details']);

    $this->actingAs($user)
        ->patch(route('categories.update', $category), ['name' => 'Groceries', 'description' => 'New details'])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('categories.index'));

    expect($category->refresh())->name->toBe('Groceries')
        ->description->toBe('New details');
});

test('a category can keep its own name', function () {
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create(['name' => 'Groceries']);

    $this->actingAs($user)
        ->patch(route('categories.update', $category), ['name' => 'GROCERIES', 'description' => 'New details'])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('categories.index'));

    expect($category->refresh()->name)->toBe('GROCERIES');
});

test('a category can take a name that another user already uses', function () {
    $user = User::factory()->create();
    Category::factory()->create(['name' => 'Groceries']);
    $category = Category::factory()->for($user)->create(['name' => 'Rent']);

    $this->actingAs($user)
        ->patch(route('categories.update', $category), ['name' => 'Groceries'])
        ->assertSessionHasNoErrors();

    expect($category->refresh()->name)->toBe('Groceries');
});
