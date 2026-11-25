<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\Transfer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transfer>
 */
class TransferFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'from_account_id' => Account::factory(),
            // The destination belongs to the same user and uses the same currency as the source, because transfers between currencies are not supported
            'to_account_id' => function (array $attributes): int {
                $source = Account::find($attributes['from_account_id']);

                return Account::factory()->create([
                    'user_id' => $source->user_id,
                    'currency_id' => $source->currency_id,
                ])->id;
            },
            'amount' => $this->faker->randomFloat(2, 10, 100),
            'date' => $this->faker->dateTimeBetween('-3 months')->format('Y-m-d'),
            'note' => $this->faker->optional()->sentence(3),
        ];
    }
}
