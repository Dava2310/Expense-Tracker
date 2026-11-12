<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\Category;
use App\Models\Income;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Income>
 */
class IncomeFactory extends Factory
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
            'account_id' => Account::factory(),
            'category_id' => Category::factory(),
        ];
    }
}
