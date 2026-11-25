<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Category;
use App\Models\Currency;
use App\Models\Expense;
use App\Models\Income;
use App\Models\Transfer;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $users = User::factory(10)->create();
        $users->push(User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]));

        // Creating currencies and accounts for every user
        foreach ($users as $user) {
            // Creating one currency for this current user
            $currency = Currency::factory()->for($user)->create();

            // Creating two accounts with the current user and the currency just created, so money can be moved between them
            $accounts = Account::factory(2)->for($user)->for($currency)->create();
            $account = $accounts->first();

            // Creating between 1-3 categories per user
            $categories = Category::factory(random_int(1, 3))->for($user)->create();

            // Creating between 1-5 incomes and expenses for this user
            Income::factory(random_int(1, 5))->recycle($categories)->for($account)->create();
            Expense::factory(random_int(1, 5))->recycle($categories)->for($account)->create();

            // Creating between 0-2 transfers from the first account to the second one
            Transfer::factory(random_int(0, 2))->create([
                'from_account_id' => $accounts[0]->id,
                'to_account_id' => $accounts[1]->id,
            ]);
        }
    }
}
