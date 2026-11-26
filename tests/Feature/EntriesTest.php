<?php

use App\Models\Account;
use App\Models\Category;
use App\Models\Currency;
use App\Models\Expense;
use App\Models\Income;
use App\Models\Transfer;
use App\Models\User;

function accountWithBalance(User $user, float $balance, string $name = 'Checking', ?Currency $currency = null): Account
{
    $currency ??= Currency::factory()->for($user)->create(['symbol' => '€']);

    return Account::factory()->for($user)->for($currency)->create(['name' => $name, 'balance' => $balance]);
}

test('an expense is saved and its amount is deducted from the account', function () {
    $user = User::factory()->create();
    $account = accountWithBalance($user, 100);
    $category = Category::factory()->for($user)->create();

    $this->actingAs($user)
        ->post(route('expenses.store'), ['amount' => '24.50', 'concept' => 'Groceries', 'date' => '2026-11-25', 'account_id' => $account->id, 'category_id' => $category->id])
        ->assertRedirect(route('expenses.index'))
        ->assertSessionHas('status', 'expense-created');

    expect(Expense::firstWhere('concept', 'Groceries')->amount)->toEqual(24.50)
        ->and($account->fresh()->balance)->toEqual(75.50);
});

test('an expense can use the whole balance of the account but not one cent more', function () {
    $user = User::factory()->create();
    $account = accountWithBalance($user, 100);
    $category = Category::factory()->for($user)->create();
    $expense = fn (string $amount) => ['amount' => $amount, 'concept' => 'Rent', 'date' => '2026-11-25', 'account_id' => $account->id, 'category_id' => $category->id];

    $this->actingAs($user)
        ->post(route('expenses.store'), $expense('100.01'))
        ->assertSessionHasErrors(['amount' => 'Checking only has €100.00 available.']);

    expect(Expense::count())->toBe(0)
        ->and($account->fresh()->balance)->toEqual(100);

    $this->post(route('expenses.store'), $expense('100.00'))->assertSessionHasNoErrors();

    expect($account->fresh()->balance)->toEqual(0);
});

test('an expense cannot use an account or a category the user cannot use', function () {
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create();
    $deactivated = Account::factory()->for($user)->inactive()->create(['balance' => 100]);
    $strangersAccount = accountWithBalance(User::factory()->create(), 100);
    $strangersCategory = Category::factory()->for(User::factory())->create();
    $own = accountWithBalance($user, 100);
    $payload = ['amount' => '10', 'concept' => 'Test', 'date' => '2026-11-25', 'account_id' => $own->id, 'category_id' => $category->id];

    $this->actingAs($user);
    $this->post(route('expenses.store'), [...$payload, 'account_id' => $deactivated->id])->assertSessionHasErrors(['account_id' => 'Choose one of your active accounts.']);
    $this->post(route('expenses.store'), [...$payload, 'account_id' => $strangersAccount->id])->assertSessionHasErrors(['account_id' => 'Choose one of your active accounts.']);
    $this->post(route('expenses.store'), [...$payload, 'category_id' => $strangersCategory->id])->assertSessionHasErrors(['category_id' => 'Choose one of your own categories.']);

    expect(Expense::count())->toBe(0);
});

test('editing an expense moves the money between the two accounts', function () {
    $user = User::factory()->create();
    $currency = Currency::factory()->for($user)->create();
    $checking = accountWithBalance($user, 80, 'Checking', $currency);
    $savings = accountWithBalance($user, 50, 'Savings', $currency);
    $category = Category::factory()->for($user)->create();
    $expense = Expense::factory()->for($checking)->for($category)->create(['amount' => 20]);

    $this->actingAs($user)
        ->patch(route('expenses.update', $expense), ['amount' => '30', 'concept' => 'Moved', 'date' => '2026-11-25', 'account_id' => $savings->id, 'category_id' => $category->id])
        ->assertRedirect(route('expenses.index'))
        ->assertSessionHas('status', 'expense-updated');

    expect($checking->fresh()->balance)->toEqual(100)
        ->and($savings->fresh()->balance)->toEqual(20)
        ->and($expense->fresh()->amount)->toEqual(30);
});

test('editing an expense to an amount the account cannot cover is refused', function () {
    $user = User::factory()->create();
    $account = accountWithBalance($user, 10);
    $category = Category::factory()->for($user)->create();
    $expense = Expense::factory()->for($account)->for($category)->create(['amount' => 5]);

    $this->actingAs($user)
        ->patch(route('expenses.update', $expense), ['amount' => '50', 'concept' => 'Too much', 'date' => '2026-11-25', 'account_id' => $account->id, 'category_id' => $category->id])
        ->assertSessionHasErrors(['amount' => 'Checking doesn’t have enough balance for this amount.']);

    expect($expense->fresh()->amount)->toEqual(5)
        ->and($account->fresh()->balance)->toEqual(10);
});

test('deleting an expense gives its amount back to the account', function () {
    $user = User::factory()->create();
    $account = accountWithBalance($user, 80);
    $expense = Expense::factory()->for($account)->for(Category::factory()->for($user))->create(['amount' => 20]);

    $this->actingAs($user)
        ->delete(route('expenses.destroy', $expense))
        ->assertRedirect(route('expenses.index'))
        ->assertSessionHas('status', 'expense-deleted');

    $this->assertModelMissing($expense);
    expect($account->fresh()->balance)->toEqual(100);
});

test('an income is saved and its amount is added to the account', function () {
    $user = User::factory()->create();
    $account = accountWithBalance($user, 100);
    $category = Category::factory()->for($user)->create();

    $this->actingAs($user)
        ->post(route('incomes.store'), ['amount' => '25.50', 'concept' => 'Salary', 'date' => '2026-11-25', 'account_id' => $account->id, 'category_id' => $category->id])
        ->assertRedirect(route('incomes.index'))
        ->assertSessionHas('status', 'income-created');

    expect(Income::firstWhere('concept', 'Salary')->amount)->toEqual(25.50)
        ->and($account->fresh()->balance)->toEqual(125.50);
});

test('editing an income whose money was already spent is refused', function () {
    $user = User::factory()->create();
    $account = accountWithBalance($user, 10);
    $category = Category::factory()->for($user)->create();
    $income = Income::factory()->for($account)->for($category)->create(['amount' => 50]);

    $this->actingAs($user)
        ->patch(route('incomes.update', $income), ['amount' => '20', 'concept' => 'Lower', 'date' => '2026-11-25', 'account_id' => $account->id, 'category_id' => $category->id])
        ->assertSessionHasErrors(['amount' => 'Checking would end up with a negative balance.']);

    expect($income->fresh()->amount)->toEqual(50)
        ->and($account->fresh()->balance)->toEqual(10);
});

test('deleting an income whose money was already spent is refused', function () {
    $user = User::factory()->create();
    $account = accountWithBalance($user, 10);
    $income = Income::factory()->for($account)->for(Category::factory()->for($user))->create(['amount' => 50]);

    $this->actingAs($user)
        ->delete(route('incomes.destroy', $income))
        ->assertRedirect(route('incomes.index'))
        ->assertSessionHasErrors(['income' => 'Checking would end up with a negative balance, so this income can’t be deleted.']);

    $this->assertModelExists($income);
    expect($account->fresh()->balance)->toEqual(10);
});

test('a transfer moves the amount from one account to the other and the total does not change', function () {
    $user = User::factory()->create();
    $currency = Currency::factory()->for($user)->create();
    $checking = accountWithBalance($user, 1000, 'Checking', $currency);
    $savings = accountWithBalance($user, 200, 'Savings', $currency);

    $this->actingAs($user)
        ->post(route('transfers.store'), ['from_account_id' => $checking->id, 'to_account_id' => $savings->id, 'amount' => '300', 'date' => '2026-11-25', 'note' => 'Savings plan'])
        ->assertRedirect(route('accounts.index'))
        ->assertSessionHas('status', 'transfer-created');

    expect($checking->fresh()->balance)->toEqual(700)
        ->and($savings->fresh()->balance)->toEqual(500)
        ->and(Transfer::count())->toBe(1);
});

test('a transfer from an account to itself is refused', function () {
    $user = User::factory()->create();
    $account = accountWithBalance($user, 100);

    $this->actingAs($user)
        ->post(route('transfers.store'), ['from_account_id' => $account->id, 'to_account_id' => $account->id, 'amount' => '10', 'date' => '2026-11-25'])
        ->assertSessionHasErrors(['to_account_id' => 'Choose a different account from the source account.']);

    expect(Transfer::count())->toBe(0)
        ->and($account->fresh()->balance)->toEqual(100);
});

test('a transfer between accounts of different currencies is refused', function () {
    $user = User::factory()->create();
    $euros = accountWithBalance($user, 100, 'Euros');
    $dollars = accountWithBalance($user, 100, 'Dollars', Currency::factory()->for($user)->create(['symbol' => '$']));

    $this->actingAs($user)
        ->post(route('transfers.store'), ['from_account_id' => $euros->id, 'to_account_id' => $dollars->id, 'amount' => '10', 'date' => '2026-11-25'])
        ->assertSessionHasErrors(['to_account_id' => 'Transfers between accounts in different currencies are not supported.']);

    expect(Transfer::count())->toBe(0);
});

test('a transfer the source account cannot cover is refused', function () {
    $user = User::factory()->create();
    $currency = Currency::factory()->for($user)->create(['symbol' => '€']);
    $checking = accountWithBalance($user, 100, 'Checking', $currency);
    $savings = accountWithBalance($user, 0, 'Savings', $currency);

    $this->actingAs($user)
        ->post(route('transfers.store'), ['from_account_id' => $checking->id, 'to_account_id' => $savings->id, 'amount' => '100.01', 'date' => '2026-11-25'])
        ->assertSessionHasErrors(['amount' => 'Checking only has €100.00 available.']);

    expect(Transfer::count())->toBe(0)
        ->and($checking->fresh()->balance)->toEqual(100)
        ->and($savings->fresh()->balance)->toEqual(0);
});

test('a transfer cannot use an account of another user', function () {
    $user = User::factory()->create();
    $own = accountWithBalance($user, 100);
    $strangers = accountWithBalance(User::factory()->create(), 100);

    $this->actingAs($user);
    $this->post(route('transfers.store'), ['from_account_id' => $own->id, 'to_account_id' => $strangers->id, 'amount' => '10', 'date' => '2026-11-25'])->assertSessionHasErrors('to_account_id');
    $this->post(route('transfers.store'), ['from_account_id' => $strangers->id, 'to_account_id' => $own->id, 'amount' => '10', 'date' => '2026-11-25'])->assertSessionHasErrors('from_account_id');

    expect(Transfer::count())->toBe(0)
        ->and($strangers->fresh()->balance)->toEqual(100);
});

test('editing a transfer so that an account would go below zero is refused', function () {
    $user = User::factory()->create();
    $currency = Currency::factory()->for($user)->create();
    $checking = accountWithBalance($user, 700, 'Checking', $currency);
    $savings = accountWithBalance($user, 100, 'Savings', $currency);
    $transfer = Transfer::factory()->create(['from_account_id' => $checking->id, 'to_account_id' => $savings->id, 'amount' => 300]);

    $this->actingAs($user)
        ->patch(route('transfers.update', $transfer), ['from_account_id' => $checking->id, 'to_account_id' => $savings->id, 'amount' => '100', 'date' => '2026-11-25'])
        ->assertSessionHasErrors(['amount' => 'Savings would end up with a negative balance.']);

    expect($transfer->fresh()->amount)->toEqual(300)
        ->and($checking->fresh()->balance)->toEqual(700)
        ->and($savings->fresh()->balance)->toEqual(100);
});

test('deleting a transfer puts both balances back', function () {
    $user = User::factory()->create();
    $currency = Currency::factory()->for($user)->create();
    $checking = accountWithBalance($user, 700, 'Checking', $currency);
    $savings = accountWithBalance($user, 500, 'Savings', $currency);
    $transfer = Transfer::factory()->create(['from_account_id' => $checking->id, 'to_account_id' => $savings->id, 'amount' => 300]);

    $this->actingAs($user)
        ->delete(route('transfers.destroy', $transfer))
        ->assertRedirect(route('accounts.index'))
        ->assertSessionHas('status', 'transfer-deleted');

    $this->assertModelMissing($transfer);
    expect($checking->fresh()->balance)->toEqual(1000)
        ->and($savings->fresh()->balance)->toEqual(200);
});

test('deleting a transfer whose money the destination already spent is refused', function () {
    $user = User::factory()->create();
    $currency = Currency::factory()->for($user)->create();
    $checking = accountWithBalance($user, 700, 'Checking', $currency);
    $savings = accountWithBalance($user, 299.99, 'Savings', $currency);
    $transfer = Transfer::factory()->create(['from_account_id' => $checking->id, 'to_account_id' => $savings->id, 'amount' => 300]);

    $this->actingAs($user)
        ->delete(route('transfers.destroy', $transfer))
        ->assertRedirect(route('accounts.index'))
        ->assertSessionHasErrors(['transfer' => 'Savings would end up with a negative balance, so this transfer can’t be deleted.']);

    $this->assertModelExists($transfer);
    expect($savings->fresh()->balance)->toEqual(299.99);
});

test('a transfer is not counted in the income and expense totals', function () {
    $user = User::factory()->create();
    $currency = Currency::factory()->for($user)->create();
    $checking = accountWithBalance($user, 1000, 'Checking', $currency);
    $savings = accountWithBalance($user, 0, 'Savings', $currency);

    $this->actingAs($user)->post(route('transfers.store'), ['from_account_id' => $checking->id, 'to_account_id' => $savings->id, 'amount' => '300', 'date' => now()->toDateString()]);

    expect($checking->monthTotal('incomes'))->toEqual(0)
        ->and($checking->monthTotal('expenses'))->toEqual(0)
        ->and($savings->monthTotal('incomes'))->toEqual(0)
        ->and($savings->monthTotal('expenses'))->toEqual(0);
});

test('a user cannot open, change or delete the expense, income or transfer of another user', function () {
    $user = User::factory()->create();
    $currency = Currency::factory()->for($user)->create();
    $mine = accountWithBalance($user, 100, 'Mine', $currency);
    $mineToo = accountWithBalance($user, 100, 'Mine too', $currency);
    $myCategory = Category::factory()->for($user)->create();

    $stranger = User::factory()->create();
    $strangersCurrency = Currency::factory()->for($stranger)->create();
    $theirs = accountWithBalance($stranger, 100, 'Theirs', $strangersCurrency);
    $theirsToo = accountWithBalance($stranger, 100, 'Theirs too', $strangersCurrency);
    $theirCategory = Category::factory()->for($stranger)->create();
    $expense = Expense::factory()->for($theirs)->for($theirCategory)->create(['concept' => 'Their expense', 'amount' => 10]);
    $income = Income::factory()->for($theirs)->for($theirCategory)->create(['concept' => 'Their income', 'amount' => 10]);
    $transfer = Transfer::factory()->create(['from_account_id' => $theirs->id, 'to_account_id' => $theirsToo->id, 'amount' => 10]);
    $entry = ['amount' => '1', 'concept' => 'Hacked', 'date' => '2026-11-25', 'account_id' => $mine->id, 'category_id' => $myCategory->id];

    $this->actingAs($user);
    $this->get(route('expenses.edit', $expense))->assertForbidden();
    $this->patch(route('expenses.update', $expense), $entry)->assertForbidden();
    $this->delete(route('expenses.destroy', $expense))->assertForbidden();
    $this->get(route('incomes.edit', $income))->assertForbidden();
    $this->patch(route('incomes.update', $income), $entry)->assertForbidden();
    $this->delete(route('incomes.destroy', $income))->assertForbidden();
    $this->get(route('transfers.edit', $transfer))->assertForbidden();
    $this->patch(route('transfers.update', $transfer), ['from_account_id' => $mine->id, 'to_account_id' => $mineToo->id, 'amount' => '1', 'date' => '2026-11-25'])->assertForbidden();
    $this->delete(route('transfers.destroy', $transfer))->assertForbidden();

    expect($expense->fresh()->concept)->toBe('Their expense')
        ->and($income->fresh()->concept)->toBe('Their income')
        ->and($transfer->fresh()->amount)->toEqual(10)
        ->and($theirs->fresh()->balance)->toEqual(100);
});

test('the expense and income lists only show the entries of the user', function () {
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create();
    $account = accountWithBalance($user, 100);
    Expense::factory()->for($account)->for($category)->create(['concept' => 'My expense']);
    Income::factory()->for($account)->for($category)->create(['concept' => 'My income']);
    $strangersAccount = accountWithBalance(User::factory()->create(), 100);
    $strangersCategory = Category::factory()->for($strangersAccount->user)->create();
    Expense::factory()->for($strangersAccount)->for($strangersCategory)->create(['concept' => 'Their expense']);
    Income::factory()->for($strangersAccount)->for($strangersCategory)->create(['concept' => 'Their income']);

    $this->actingAs($user);
    $this->get(route('expenses.index'))->assertOk()->assertSee('My expense')->assertDontSee('Their expense');
    $this->get(route('incomes.index'))->assertOk()->assertSee('My income')->assertDontSee('Their income');
});
