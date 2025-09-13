<?php

namespace App\Services;

use App\Events\AchievementUnlocked;
use App\Models\Achievement;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AchievementService
{
    /**
     * @return array<int, Achievement>
     */
    public function checkAndUnlockAchievements(User $user): array
    {
        $unlockedAchievements = [];
        $userAchievementIds = $user->achievements->pluck('id')->toArray();

        $availableAchievements = Achievement::active()
            ->whereNotIn('id', $userAchievementIds)
            ->get();

        foreach ($availableAchievements as $achievement) {
            if ($this->checkAchievementCriteria($user, $achievement)) {
                $this->unlockAchievement($user, $achievement);
                $unlockedAchievements[] = $achievement;
            }
        }

        return $unlockedAchievements;
    }

    /**
     * Calculate achievement progress for a user
     */
    public function calculateAchievementProgress(User $user, Achievement $achievement): int
    {
        $criteria = $achievement->criteria;

        if (isset($criteria['transaction_count'])) {
            $currentCount = $user->transactions()->where('transaction_type', 'purchase')->count();
            $requiredCount = $criteria['transaction_count'];

            return min(100, (int) round(($currentCount / $requiredCount) * 100));
        }

        if (isset($criteria['points_minimum'])) {
            $currentPoints = $user->total_points;
            $requiredPoints = $criteria['points_minimum'];

            return min(100, (int) round(($currentPoints / $requiredPoints) * 100));
        }

        if (isset($criteria['single_transaction_amount'])) {
            $maxAmount = $user->transactions()
                ->where('transaction_type', 'purchase')
                ->max('amount') ?? 0;
            $requiredAmount = $criteria['single_transaction_amount'];

            return min(100, (int) round(($maxAmount / $requiredAmount) * 100));
        }

        return 0;
    }

    private function checkAchievementCriteria(User $user, Achievement $achievement): bool
    {
        $criteria = $achievement->criteria;

        // Skip if criteria is null or empty
        if ($criteria === null || empty($criteria)) {
            return false;
        }

        // Handle multiple criteria - all must be met
        foreach ($criteria as $criterion => $value) {
            switch ($criterion) {
                case 'transaction_count':
                    $count = $user->transactions()->where('transaction_type', 'purchase')->count();
                    if ($count < $value) {
                        return false;
                    }
                    break;

                case 'points_minimum':
                    if ($user->total_points < $value) {
                        return false;
                    }
                    break;

                case 'single_transaction_amount':
                    $maxAmount = $user->transactions()
                        ->where('transaction_type', 'purchase')
                        ->max('amount') ?? 0;
                    if ($maxAmount < $value) {
                        return false;
                    }
                    break;

                case 'total_spending':
                    $totalSpending = $user->transactions()
                        ->where('transaction_type', 'purchase')
                        ->sum('amount');
                    if ($totalSpending < $value) {
                        return false;
                    }
                    break;

                default:
                    // Unknown criterion
                    return false;
            }
        }

        return true;
    }

    private function unlockAchievement(User $user, Achievement $achievement): void
    {
        DB::transaction(function () use ($user, $achievement) {
            // Check if achievement is already unlocked
            if ($user->achievements()->where('achievement_id', $achievement->id)->exists()) {
                return; // Already unlocked, skip
            }

            $user->achievements()->attach($achievement->id, [
                'unlocked_at' => now(),
            ]);

            // Log the event
            $this->logEvent($user, 'achievement_unlocked', [
                'achievement_id' => $achievement->id,
                'achievement_name' => $achievement->name,
            ]);

            // Broadcast the event
            event(new AchievementUnlocked($user, $achievement));
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function logEvent(User $user, string $eventType, array $data): void
    {
        DB::table('events')->insert([
            'user_id' => $user->id,
            'event_type' => $eventType,
            'event_data' => json_encode($data),
            'created_at' => now(),
        ]);
    }
}
