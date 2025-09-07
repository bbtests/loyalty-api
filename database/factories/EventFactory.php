<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\Illuminate\Database\Eloquent\Model>
 */
class EventFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => $this->faker->optional()->randomElement([User::factory(), null]),
            'event_type' => $this->faker->randomElement(['login', 'purchase', 'achievement_unlocked', 'badge_earned']),
            'event_data' => json_encode(['info' => $this->faker->sentence()]),
            'created_at' => $this->faker->dateTimeThisYear(),
        ];
    }
}
