<?php

use App\Models\Category;
use App\Models\Expense;
use App\Models\Income;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $category = Category::factory()->create();

    $this->delete(route('categories.destroy', $category))->assertRedirect(route('login'));

    $this->assertModelExists($category);
});

test('the owner deletes a category that is not used and it is removed from the database', function () {
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create();

    $this->actingAs($user)
        ->delete(route('categories.destroy', $category))
        ->assertRedirect(route('categories.index'))
        ->assertSessionHas('status', 'category-deleted');

    $this->assertDatabaseMissing('categories', ['id' => $category->id]);
});

test('a category used by an entry cannot be deleted', function (string $model) {
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create(['name' => 'Groceries']);
    $model::factory()->for($category)->create();

    $this->actingAs($user)
        ->delete(route('categories.destroy', $category))
        ->assertRedirect(route('categories.index'))
        ->assertSessionHasErrors(['category' => 'Groceries is in use, so it can’t be deleted.']);

    $this->assertModelExists($category);
})->with([
    'income' => [Income::class],
    'expense' => [Expense::class],
]);

test('another user cannot delete the category', function () {
    $category = Category::factory()->create();

    $this->actingAs(User::factory()->create())
        ->delete(route('categories.destroy', $category))
        ->assertForbidden();

    $this->assertModelExists($category);
});

test('the list offers the matching dialog for each category', function () {
    $user = User::factory()->create();
    $unused = Category::factory()->for($user)->create();
    $used = Category::factory()->for($user)->create();
    Income::factory()->count(2)->for($used)->create();

    $this->actingAs($user)
        ->get(route('categories.index'))
        ->assertOk()
        ->assertSee('category-in-use', false)
        ->assertSee('confirm-category-deletion', false)
        ->assertSee(route('categories.destroy', $unused), false)
        ->assertSee('2 entries')
        ->assertSee('Delete category');
});

test('the list confirms a deleted category', function () {
    $this->actingAs(User::factory()->create())
        ->withSession(['status' => 'category-deleted'])
        ->get(route('categories.index'))
        ->assertSee('Category deleted.');
});

test('the list explains why a deletion was refused', function () {
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create(['name' => 'Groceries']);
    Income::factory()->for($category)->create();

    $this->actingAs($user)
        ->followingRedirects()
        ->delete(route('categories.destroy', $category))
        ->assertSee('Groceries is in use, so it can’t be deleted.');
});
