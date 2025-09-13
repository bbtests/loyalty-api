<?php

namespace App\Services;

use App\Jobs\ProcessPurchaseEvent;
use App\Models\LoyaltyPoint;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LoyaltyService
{
    public function __construct()
    {
        // No dependencies needed for job-based approach
    }

    public function processPurchase(User $user, float $amount, ?string $externalTransactionId = null): Transaction
    {
        return DB::transaction(function () use ($user, $amount, $externalTransactionId) {
            // Calculate loyalty points (configurable rate)
            $pointsPerCurrency = config('loyalty.points_per_currency', 10);
            $pointsEarned = (int) floor($amount * $pointsPerCurrency);

            // Create transaction record
            $transaction = Transaction::create([
                'user_id' => $user->id,
                'amount' => $amount,
                'points_earned' => $pointsEarned,
                'transaction_type' => 'purchase',
                'external_transaction_id' => $externalTransactionId,
                'status' => 'completed',
                'metadata' => [
                    'points_rate' => $pointsPerCurrency,
                    'processed_at' => now(),
                ],
            ]);

            // Update or create loyalty points record
            $loyaltyPoints = LoyaltyPoint::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'points' => 0,
                    'total_earned' => 0,
                    'total_redeemed' => 0,
                ]
            );

            $loyaltyPoints->addPoints($pointsEarned);

            // Dispatch job for achievement/badge processing
            // This will use the configured queue connection (Redis or RabbitMQ)
            ProcessPurchaseEvent::dispatch($user->id, $transaction->id);

            Log::info('Purchase event job dispatched', [
                'user_id' => $user->id,
                'transaction_id' => $transaction->id,
                'queue_connection' => config('queue.default'),
            ]);

            return $transaction;
        });
    }

    public function redeemPoints(User $user, int $points): bool
    {
        $loyaltyPoints = $user->loyaltyPoints;

        if (! $loyaltyPoints || ! $loyaltyPoints->redeemPoints($points)) {
            return false;
        }

        // Create redemption transaction
        Transaction::create([
            'user_id' => $user->id,
            'amount' => 0,
            'points_earned' => -$points,
            'transaction_type' => 'redemption',
            'status' => 'completed',
            'metadata' => [
                'points_redeemed' => $points,
                'redeemed_at' => now(),
            ],
        ]);
        $user->load('loyaltyPoints');

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function getUserLoyaltyData(User $user): array
    {
        $user->load(['loyaltyPoints', 'achievements', 'badges']);

        return [
            'user_id' => $user->id,
            'points' => [
                'available' => $user->available_points,
                'total_earned' => $user->total_points,
                'total_redeemed' => $user->loyaltyPoints->total_redeemed ?? 0,
            ],
            'achievements' => $user->achievements->map(function ($achievement) {
                return [
                    'id' => $achievement->id,
                    'name' => $achievement->name,
                    'description' => $achievement->description,
                    'badge_icon' => $achievement->badge_icon,
                    'unlocked_at' => $achievement->pivot->unlocked_at ?? null,
                ];
            }),
            'badges' => $user->badges->map(function ($badge) {
                return [
                    'id' => $badge->id,
                    'name' => $badge->name,
                    'description' => $badge->description,
                    'icon' => $badge->icon,
                    'tier' => $badge->tier,
                    'earned_at' => $badge->pivot->earned_at ?? null,
                ];
            }),
            'current_badge' => $user->getCurrentBadge(),
        ];
    }

    /**
     * Award points to a user
     */
    public function awardPoints(User $user, int $points): void
    {
        $loyaltyPoints = $user->loyaltyPoints;

        if ($loyaltyPoints === null) {
            $loyaltyPoints = LoyaltyPoint::create([
                'user_id' => $user->id,
                'points' => $points,
                'total_earned' => $points,
                'total_redeemed' => 0,
            ]);
        } else {
            $loyaltyPoints->increment('points', $points);
            $loyaltyPoints->increment('total_earned', $points);
        }

        // Refresh the user's loyalty points relationship
        $user->load('loyaltyPoints');
    }

    /**
     * Get user's total points
     */
    public function getUserTotalPoints(User $user): int
    {
        return $user->available_points;
    }

    /**
     * Check achievements for a user
     */
    public function checkAchievements(User $user): void
    {
        $achievements = \App\Models\Achievement::where('is_active', true)->get();

        foreach ($achievements as $achievement) {
            $criteria = $achievement->criteria;

            // Skip if criteria is null or empty
            if (! $criteria) {
                continue;
            }

            if ($this->meetsAchievementCriteria($user, $criteria)) {
                // Check if user already has this achievement
                if (! $user->achievements()->where('achievement_id', $achievement->id)->exists()) {
                    // Award the achievement
                    $user->achievements()->attach($achievement->id, [
                        'unlocked_at' => now(),
                        'progress' => 100,
                    ]);

                    // Dispatch the event
                    event(new \App\Events\AchievementUnlocked($user, $achievement));
                }
            }
        }
    }

    /**
     * Check if user meets achievement criteria
     *
     * @param  array<string, mixed>  $criteria
     */
    private function meetsAchievementCriteria(User $user, array $criteria): bool
    {
        foreach ($criteria as $key => $value) {
            switch ($key) {
                case 'transaction_count':
                    $userTransactionCount = $user->transactions()->count();
                    if ($userTransactionCount < $value) {
                        return false;
                    }
                    break;
                case 'points_earned':
                    $userPointsEarned = $user->total_points;
                    if ($userPointsEarned < $value) {
                        return false;
                    }
                    break;
                    // Add more criteria as needed
            }
        }

        return true;
    }
}
