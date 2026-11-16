<?php

use App\Models\Category;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $this->get(route('categories.create'))->assertRedirect(route('login'));
    $this->post(route('categories.store'), [])->assertRedirect(route('login'));
});

test('the new category form asks for a name and optional details', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('categories.create'))
        ->assertOk()
        ->assertSee('New category')
        ->assertSee('name="name"', false)
        ->assertSee('name="description"', false)
        ->assertSee(route('categories.store'), false);
});

test('a user creates a category that belongs to them', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('categories.store'), ['name' => 'Pets', 'description' => 'Food and vet visits'])
        ->assertRedirect(route('categories.index'))
        ->assertSessionHas('status', 'category-created');

    $category = Category::firstWhere('name', 'Pets');

    expect($category->user_id)->toBe($user->id)
        ->and($category->description)->toBe('Food and vet visits');
});

test('the details are optional', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('categories.store'), ['name' => 'Pets', 'description' => ''])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('categories.index'));

    expect(Category::firstWhere('name', 'Pets')->description)->toBeNull();
});

test('a submitted user is ignored when creating a category', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $this->actingAs($user)->post(route('categories.store'), ['name' => 'Pets', 'user_id' => $otherUser->id]);

    expect(Category::firstWhere('name', 'Pets')->user_id)->toBe($user->id);
});

test('invalid input is rejected, nothing is saved and the errors are shown', function (array $input, string $invalid) {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('categories.create'))
        ->post(route('categories.store'), $input)
        ->assertRedirect(route('categories.create'))
        ->assertSessionHasErrors($invalid);

    expect(Category::count())->toBe(0);

    $this->actingAs($user)
        ->get(route('categories.create'))
        ->assertSee('aria-invalid="true"', false);
})->with([
    'missing name' => [['name' => ''], 'name'],
    'name too long' => [['name' => str_repeat('a', 256)], 'name'],
    'details too long' => [['name' => 'Pets', 'description' => str_repeat('a', 256)], 'description'],
]);

test('a user cannot have two categories with the same name, whatever the letter case', function () {
    $user = User::factory()->create();
    Category::factory()->for($user)->create(['name' => 'Groceries']);

    $this->actingAs($user)
        ->post(route('categories.store'), ['name' => 'groceries'])
        ->assertSessionHasErrors(['name' => 'You already have a category called groceries.']);

    expect(Category::count())->toBe(1);
});

test('two users can have categories with the same name', function () {
    Category::factory()->create(['name' => 'Groceries']);

    $this->actingAs(User::factory()->create())
        ->post(route('categories.store'), ['name' => 'Groceries'])
        ->assertSessionHasNoErrors();

    expect(Category::where('name', 'Groceries')->count())->toBe(2);
});
