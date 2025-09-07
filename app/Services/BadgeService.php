<?php

namespace App\Services;

use App\Events\BadgeUnlocked;
use App\Models\Badge;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class BadgeService
{
    /**
     * @return array<int, Badge>
     */
    public function checkAndUnlockBadges(User $user): array
    {
        $unlockedBadges = [];
        $userBadgeIds = $user->badges->pluck('id')->toArray();

        $availableBadges = Badge::active()
            ->whereNotIn('id', $userBadgeIds)
            ->orderBy('tier')
            ->get();

        foreach ($availableBadges as $badge) {
            if ($this->checkBadgeRequirements($user, $badge)) {
                $this->unlockBadge($user, $badge);
                $unlockedBadges[] = $badge;
            }
        }

        return $unlockedBadges;
    }

    private function checkBadgeRequirements(User $user, Badge $badge): bool
    {
        $requirements = $badge->requirements;

        // Check points requirement
        if (isset($requirements['points_minimum'])) {
            if ($user->total_points < $requirements['points_minimum']) {
                return false;
            }
        }

        // Check purchases requirement
        if (isset($requirements['purchases_minimum'])) {
            $purchaseCount = $user->transactions()
                ->where('transaction_type', 'purchase')
                ->count();

            if ($purchaseCount < $requirements['purchases_minimum']) {
                return false;
            }
        }

        // Check spending requirement
        if (isset($requirements['spending_minimum'])) {
            $totalSpending = $user->transactions()
                ->where('transaction_type', 'purchase')
                ->sum('amount');

            if ($totalSpending < $requirements['spending_minimum']) {
                return false;
            }
        }

        return true;
    }

    private function unlockBadge(User $user, Badge $badge): void
    {
        DB::transaction(function () use ($user, $badge) {
            $user->badges()->attach($badge->id, [
                'earned_at' => now(),
            ]);

            // Log the event
            $this->logEvent($user, 'badge_unlocked', [
                'badge_id' => $badge->id,
                'badge_name' => $badge->name,
                'badge_tier' => $badge->tier,
            ]);

            // Broadcast the event
            event(new BadgeUnlocked($user, $badge));
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
