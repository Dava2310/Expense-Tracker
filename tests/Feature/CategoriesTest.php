<?php

use App\Models\Account;
use App\Models\Category;
use App\Models\Expense;
use App\Models\User;

test('a user creates a category that belongs to them', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('categories.store'), ['name' => 'Pets', 'description' => 'Food and vet'])
        ->assertRedirect(route('categories.index'))
        ->assertSessionHas('status', 'category-created');

    $category = Category::firstWhere('name', 'Pets');

    expect($category->user_id)->toBe($user->id)
        ->and($category->description)->toBe('Food and vet');
});

test('a user updates the details of a category and keeps its own name', function () {
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create(['name' => 'Pets', 'description' => 'Old']);

    $this->actingAs($user)
        ->patch(route('categories.update', $category), ['name' => 'Pets', 'description' => 'New details'])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('categories.index'))
        ->assertSessionHas('status', 'category-updated');

    expect($category->fresh()->description)->toBe('New details');
});

test('creating a category with a name the user already has is refused, whatever the letter case', function () {
    $user = User::factory()->create();
    Category::factory()->for($user)->create(['name' => 'Travel']);

    $this->actingAs($user)
        ->post(route('categories.store'), ['name' => 'travel'])
        ->assertSessionHasErrors(['name' => 'You already have a category called travel.']);

    expect($user->categories()->count())->toBe(1);
});

test('renaming a category to the name of another category of the user is refused', function () {
    $user = User::factory()->create();
    Category::factory()->for($user)->create(['name' => 'Travel']);
    $category = Category::factory()->for($user)->create(['name' => 'Pets']);

    $this->actingAs($user)
        ->patch(route('categories.update', $category), ['name' => 'TRAVEL'])
        ->assertSessionHasErrors(['name' => 'You already have a category called TRAVEL.']);

    expect($category->fresh()->name)->toBe('Pets');
});

test('two users can have a category with the same name', function () {
    $user = User::factory()->create();
    Category::factory()->for(User::factory())->create(['name' => 'Travel']);

    $this->actingAs($user)
        ->post(route('categories.store'), ['name' => 'Travel'])
        ->assertSessionHasNoErrors();

    expect($user->categories()->where('name', 'Travel')->exists())->toBeTrue();
});

test('a category used by an entry cannot be deleted', function () {
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create();
    Expense::factory()->for(Account::factory()->for($user))->for($category)->create();

    $this->actingAs($user)
        ->delete(route('categories.destroy', $category))
        ->assertRedirect(route('categories.index'))
        ->assertSessionHasErrors('category');

    $this->assertModelExists($category);
});

test('a category that no entry uses is deleted', function () {
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create();

    $this->actingAs($user)
        ->delete(route('categories.destroy', $category))
        ->assertRedirect(route('categories.index'))
        ->assertSessionHas('status', 'category-deleted');

    $this->assertModelMissing($category);
});

test('a user cannot edit, update or delete the category of another user', function () {
    $user = User::factory()->create();
    $category = Category::factory()->for(User::factory())->create(['name' => 'Private']);

    $this->actingAs($user);
    $this->get(route('categories.edit', $category))->assertForbidden();
    $this->patch(route('categories.update', $category), ['name' => 'Hacked'])->assertForbidden();
    $this->delete(route('categories.destroy', $category))->assertForbidden();

    expect($category->fresh()->name)->toBe('Private');
});
