<?php

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

test('the database refuses a second category with the same name for the same user', function () {
    $user = User::factory()->create();
    $insertGroceries = fn () => DB::table('categories')->insert(['name' => 'Groceries', 'user_id' => $user->id]);

    $insertGroceries();

    expect($insertGroceries)->toThrow(UniqueConstraintViolationException::class)
        ->and(Category::count())->toBe(1);
});

test('two different users can each have a category with the same name', function () {
    Category::factory()->for(User::factory())->create(['name' => 'Groceries']);
    Category::factory()->for(User::factory())->create(['name' => 'Groceries']);

    expect(Category::where('name', 'Groceries')->count())->toBe(2);
});
