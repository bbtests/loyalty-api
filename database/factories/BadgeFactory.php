<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Badge>
 */
class BadgeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->word().' Badge',
            'description' => $this->faker->sentence(),
            'requirements' => json_encode([
                'points_minimum' => $this->faker->numberBetween(0, 25000),
                'purchases_minimum' => $this->faker->numberBetween(0, 100),
            ]),
            'icon' => $this->faker->randomElement(['bronze-medal', 'silver-medal', 'gold-medal', 'platinum-medal']),
            'tier' => $this->faker->numberBetween(1, 4),
            'is_active' => $this->faker->boolean(90),
        ];
    }
}
