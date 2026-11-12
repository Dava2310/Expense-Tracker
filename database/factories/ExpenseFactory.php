<?php

namespace Database\Factories;

use App\Models\Expense;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Expense>
 */
class ExpenseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'amount' => $this->faker->randomFloat(2, 10, 100),
            'concept' => $this->faker->paragraph(2, true),
            'date' => $this->faker->date(),
            'account_id' => $this->faker->numberBetween(1, 10),
        ];
    }
}
