<?php

namespace Database\Seeders;

use App\Models\Achievement;
use App\Models\User;
use App\Models\UserAchievement;
use Illuminate\Database\Seeder;

class UserAchievementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::where('email', '!=', 'superadmin@example.com')->get();
        $achievements = Achievement::all();

        foreach ($users as $user) {
            // Randomly assign 1-3 achievements per user
            $numAchievements = rand(1, min(3, $achievements->count()));
            $selectedAchievements = $achievements->random($numAchievements);

            foreach ($selectedAchievements as $achievement) {
                UserAchievement::create([
                    'user_id' => $user->id,
                    'achievement_id' => $achievement->id,
                    'unlocked_at' => now()->subDays(rand(1, 30)),
                    'progress' => 100, // Assume they've completed the achievement
                ]);
            }
        }
    }
}
