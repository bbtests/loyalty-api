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

    private function checkAchievementCriteria(User $user, Achievement $achievement): bool
    {
        switch ($achievement->name) {
            case 'First Purchase':
                return $user->transactions()->where('transaction_type', 'purchase')->exists();

            case 'Loyal Customer':
            case 'Point Master':
                return $user->total_points >= $achievement->points_required;

            case 'Big Spender':
                return $user->transactions()
                    ->where('transaction_type', 'purchase')
                    ->where('amount', '>=', 500)
                    ->exists();

            case 'Frequent Buyer':
                return $user->transactions()
                    ->where('transaction_type', 'purchase')
                    ->count() >= 10;

            default:
                // Custom achievement logic can be added here
                return false;
        }
    }

    private function unlockAchievement(User $user, Achievement $achievement): void
    {
        DB::transaction(function () use ($user, $achievement) {
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
