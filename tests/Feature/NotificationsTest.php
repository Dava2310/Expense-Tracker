<?php

use App\Models\Account;
use App\Models\Currency;
use App\Models\User;
use App\Notifications\LowBalanceNotification;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Support\Facades\Queue;

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
