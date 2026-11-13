<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Account>
 */
class AccountFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'bank' => $this->faker->text(15),
            'details' => $this->faker->paragraph(2, true),
            'balance' => $this->faker->randomFloat(2, 10, 100),
            'min_balance' => $this->faker->randomFloat(2, 10, 100),
            'user_id' => User::factory(),
        ];
    }

    /**
     * Indicate that the account has been deactivated.
     */
    public function inactive(): static
    {
        return $this->state(fn (): array => ['deleted_at' => now()]);
    }
}
