<?php

use App\Models\Account;
use App\Models\Category;
use App\Models\Currency;
use App\Models\User;
use App\Notifications\LowBalanceNotification;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Support\Facades\Queue;

test('an expense that takes an account below its minimum queues one email, only for the owner', function () {
    Queue::fake();
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create(['balance' => 150, 'min_balance' => 100]);
    $category = Category::factory()->for($user)->create();
    User::factory()->create();

    $this->actingAs($user)
        ->post(route('expenses.store'), ['amount' => '80', 'concept' => 'Rent', 'date' => '2026-11-25', 'account_id' => $account->id, 'category_id' => $category->id])
        ->assertSessionHasNoErrors();

    $queued = Queue::pushed(SendQueuedNotifications::class);

    expect($queued)->toHaveCount(1)
        ->and($queued->first()->notification)->toBeInstanceOf(LowBalanceNotification::class)
        ->and($queued->first()->notifiables)->toHaveCount(1)
        ->and($queued->first()->notifiables->first()->is($user))->toBeTrue();
});

test('an expense that leaves the balance at the minimum or above it queues no email', function () {
    Queue::fake();
    $user = User::factory()->create();
    $atTheMinimum = Account::factory()->for($user)->create(['balance' => 150, 'min_balance' => 100]);
    $wellAbove = Account::factory()->for($user)->create(['balance' => 500, 'min_balance' => 100]);
    $category = Category::factory()->for($user)->create();

    $this->actingAs($user);
    $this->post(route('expenses.store'), ['amount' => '50', 'concept' => 'Rent', 'date' => '2026-11-25', 'account_id' => $atTheMinimum->id, 'category_id' => $category->id]);
    $this->post(route('expenses.store'), ['amount' => '100', 'concept' => 'Rent', 'date' => '2026-11-25', 'account_id' => $wellAbove->id, 'category_id' => $category->id]);

    Queue::assertNothingPushed();
});

test('no email is queued while the account is already below its minimum, and one is queued again after it recovers and drops', function () {
    Queue::fake();
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create(['balance' => 90, 'min_balance' => 100]);
    $category = Category::factory()->for($user)->create();
    $expense = fn (string $amount) => ['amount' => $amount, 'concept' => 'Test', 'date' => '2026-11-25', 'account_id' => $account->id, 'category_id' => $category->id];

    $this->actingAs($user)->post(route('expenses.store'), $expense('10'));

    Queue::assertNothingPushed();

    $this->post(route('incomes.store'), $expense('50'));
    $this->post(route('expenses.store'), $expense('60'));

    expect(Queue::pushed(SendQueuedNotifications::class))->toHaveCount(1);
});

test('the low-balance email is queued and names the account, its balance and its minimum', function () {
    Queue::fake();
    $user = User::factory()->create(['name' => 'Ada Lovelace']);
    $currency = Currency::factory()->for($user)->create(['symbol' => '€']);
    $account = Account::factory()->for($user)->for($currency)->create(['name' => 'Checking', 'balance' => 70, 'min_balance' => 100]);

    $notification = new LowBalanceNotification($account, 80);
    $user->notify($notification);
    $email = (string) $notification->toMail($user)->render();

    Queue::assertPushed(SendQueuedNotifications::class);
    expect($email)->toContain('Checking')
        ->toContain('€70.00')
        ->toContain('€100.00')
        ->toContain('Hi Ada,');
});

test('a transfer that takes the source account below its minimum queues one email about the source only', function () {
    Queue::fake();
    $user = User::factory()->create();
    $currency = Currency::factory()->for($user)->create();
    $checking = Account::factory()->for($user)->for($currency)->create(['name' => 'Checking', 'balance' => 150, 'min_balance' => 100]);
    $savings = Account::factory()->for($user)->for($currency)->create(['name' => 'Savings', 'balance' => 20, 'min_balance' => 100]);

    $this->actingAs($user)
        ->post(route('transfers.store'), ['from_account_id' => $checking->id, 'to_account_id' => $savings->id, 'amount' => '80', 'date' => '2026-11-25'])
        ->assertSessionHasNoErrors();

    $queued = Queue::pushed(SendQueuedNotifications::class);
    $email = (string) $queued->first()->notification->toMail($user)->render();

    expect($queued)->toHaveCount(1)
        ->and($email)->toContain('Checking')
        ->not->toContain('Savings');
});
