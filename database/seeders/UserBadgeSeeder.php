<?php

namespace Database\Seeders;

use App\Models\Badge;
use App\Models\LoyaltyPoint;
use App\Models\User;
use App\Models\UserBadge;
use Illuminate\Database\Seeder;

class UserBadgeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::where('email', '!=', 'superadmin@example.com')->get();
        $badges = Badge::all();

        foreach ($users as $user) {
            // Get user's loyalty points to determine which badges they should have
            $loyaltyPoint = LoyaltyPoint::where('user_id', $user->id)->first();
            $userPoints = $loyaltyPoint ? $loyaltyPoint->points : 0;

            foreach ($badges as $badge) {
                $requirements = $badge->requirements; // Already an array due to model casting
                $pointsMinimum = $requirements['points_minimum'] ?? 0;
                $purchasesMinimum = $requirements['purchases_minimum'] ?? 0;

                // Check if user qualifies for this badge
                $qualifiesForBadge = $userPoints >= $pointsMinimum;

                // For purchases minimum, we'll simulate based on points (roughly 1 purchase per 100 points)
                $estimatedPurchases = floor($userPoints / 100);
                $qualifiesForBadge = $qualifiesForBadge && $estimatedPurchases >= $purchasesMinimum;

                if ($qualifiesForBadge) {
                    UserBadge::create([
                        'user_id' => $user->id,
                        'badge_id' => $badge->id,
                        'earned_at' => now()->subDays(rand(1, 60)),
                    ]);
                }
            }
        }
    }
}
