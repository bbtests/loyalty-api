<?php

declare(strict_types=1);

namespace App\Observers;

use App\Events\BadgeUnlocked;
use App\Models\UserBadge;

class UserBadgeObserver
{
    public bool $afterCommit = true;

    /**
     * Handle the UserBadge "created" event.
     */
    public function created(UserBadge $userBadge): void
    {
        if ($userBadge->earned_at !== null) {
            $this->triggerBadgeUnlockedEvent($userBadge);
        }
    }

    /**
     * Handle the UserBadge "updated" event.
     */
    public function updated(UserBadge $userBadge): void
    {
        if ($userBadge->wasChanged('earned_at') && $userBadge->earned_at !== null) {
            $this->triggerBadgeUnlockedEvent($userBadge);
        }
    }

    /**
     * Trigger the badge unlocked event
     */
    protected function triggerBadgeUnlockedEvent(UserBadge $userBadge): void
    {
        $userBadge->load(['user', 'badge']);
        event(new BadgeUnlocked($userBadge->user, $userBadge->badge));
    }
}
