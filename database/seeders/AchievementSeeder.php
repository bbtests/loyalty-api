<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class AchievementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \DB::table('achievements')->insert([
            [
                'name' => 'First Purchase',
                'description' => 'Make your first purchase',
                'points_required' => 0,
                'badge_icon' => 'trophy',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Loyal Customer',
                'description' => 'Earn 1000 loyalty points',
                'points_required' => 1000,
                'badge_icon' => 'star',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Big Spender',
                'description' => 'Spend over $500 in a single transaction',
                'points_required' => 0,
                'badge_icon' => 'diamond',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Frequent Buyer',
                'description' => 'Make 10 purchases',
                'points_required' => 0,
                'badge_icon' => 'repeat',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Point Master',
                'description' => 'Earn 5000 loyalty points',
                'points_required' => 5000,
                'badge_icon' => 'crown',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
