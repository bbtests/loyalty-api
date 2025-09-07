<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Transaction>
 */
class TransactionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'amount' => $this->faker->randomFloat(2, 1, 1000),
            'points_earned' => $this->faker->numberBetween(0, 1000),
            'transaction_type' => $this->faker->randomElement(['purchase', 'cashback', 'redemption']),
            'external_transaction_id' => $this->faker->uuid(),
            'status' => $this->faker->randomElement(['completed', 'pending', 'failed']),
            'metadata' => json_encode(['note' => $this->faker->sentence()]),
        ];
    }
}
