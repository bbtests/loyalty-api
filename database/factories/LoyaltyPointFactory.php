<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\LoyaltyPoint>
 */
class LoyaltyPointFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'points' => $this->faker->numberBetween(0, 10000),
            'total_earned' => $this->faker->numberBetween(0, 20000),
            'total_redeemed' => $this->faker->numberBetween(0, 10000),
        ];
    }
}
