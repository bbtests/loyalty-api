<?php

declare(strict_types=1);

namespace App\Observers;

use App\Events\AchievementUnlocked;
use App\Models\UserAchievement;

class UserAchievementObserver
{
    /**
     * Handle the UserAchievement "created" event.
     */
    public function created(UserAchievement $userAchievement): void
    {
        if ($userAchievement->unlocked_at !== null) {
            $this->triggerAchievementUnlockedEvent($userAchievement);
        }
    }

    /**
     * Handle the UserAchievement "updated" event.
     */
    public function updated(UserAchievement $userAchievement): void
    {
        if ($userAchievement->wasChanged('unlocked_at') && $userAchievement->unlocked_at !== null) {
            $this->triggerAchievementUnlockedEvent($userAchievement);
        }
    }

    /**
     * Trigger the achievement unlocked event
     */
    protected function triggerAchievementUnlockedEvent(UserAchievement $userAchievement): void
    {
        $userAchievement->load(['user', 'achievement']);
        event(new AchievementUnlocked($userAchievement->user, $userAchievement->achievement));
    }
}
