<?php

namespace Database\Factories;

use App\Models\Badge;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\Illuminate\Database\Eloquent\Model>
 */
class UserBadgeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'badge_id' => Badge::factory(),
            'earned_at' => $this->faker->dateTimeThisYear(),
        ];
    }
}
