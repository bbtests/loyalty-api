<?php

namespace Database\Factories;

use App\Models\Achievement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\Illuminate\Database\Eloquent\Model>
 */
class UserAchievementFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'achievement_id' => Achievement::factory(),
            'unlocked_at' => $this->faker->dateTimeThisYear(),
        ];
    }
}
