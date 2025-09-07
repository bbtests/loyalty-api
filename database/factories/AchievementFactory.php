<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Achievement>
 */
class AchievementFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->word(),
            'description' => $this->faker->sentence(),
            'points_required' => $this->faker->numberBetween(0, 5000),
            'badge_icon' => $this->faker->randomElement(['trophy', 'star', 'diamond', 'repeat', 'crown']),
            'is_active' => $this->faker->boolean(90),
            'criteria' => json_encode(['transaction_count' => 1]),
        ];
    }
}
