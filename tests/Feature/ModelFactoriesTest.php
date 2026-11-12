<?php

use App\Models\Account;
use App\Models\Category;
use App\Models\Expense;
use App\Models\Income;
use App\Models\User;

test('factory creates its parent records on an empty database', function (string $model, array $parents) {
    $record = $model::factory()->create();

    $this->assertModelExists($record);

    foreach ($parents as $parent) {
        expect($parent::count())->toBeGreaterThanOrEqual(1);
    }
})->with([
    'account' => [Account::class, [User::class]],
    'expense' => [Expense::class, [Account::class, Category::class, User::class]],
    'income' => [Income::class, [Account::class, Category::class, User::class]],
]);

test('factory uses the provided parent instead of creating another', function () {
    $account = Account::factory()->create();
    $category = Category::factory()->create();

    $expense = Expense::factory()->recycle($account)->recycle($category)->create();

    expect($expense->account_id)->toBe($account->id)
        ->and($expense->category_id)->toBe($category->id)
        ->and(Account::count())->toBe(1)
        ->and(User::count())->toBe(1);
});
