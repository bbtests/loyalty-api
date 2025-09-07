<?php

namespace Database\Seeders;

use App\Models\LoyaltyPoint;
use App\Models\User;
use Illuminate\Database\Seeder;

class LoyaltyPointSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::where('email', '!=', 'superadmin@example.com')->get();

        foreach ($users as $user) {
            // Create realistic loyalty point data
            $pointsEarned = rand(500, 15000); // Random points between 500-15000
            $pointsRedeemed = rand(0, min($pointsEarned * 0.3, 2000)); // Max 30% redeemed, cap at 2000
            $currentPoints = $pointsEarned - $pointsRedeemed;

            LoyaltyPoint::create([
                'user_id' => $user->id,
                'points' => $currentPoints,
                'total_earned' => $pointsEarned,
                'total_redeemed' => $pointsRedeemed,
                'created_at' => now()->subDays(rand(1, 90)),
                'updated_at' => now()->subDays(rand(1, 7)),
            ]);
        }
    }
}
