<?php

namespace App\Listeners;

use App\Events\PurchaseProcessed;
use App\Services\AchievementService;
use App\Services\BadgeService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class ProcessLoyaltyRewards implements ShouldQueue
{
    use InteractsWithQueue;

    private AchievementService $achievementService;

    private BadgeService $badgeService;

    public function __construct(AchievementService $achievementService, BadgeService $badgeService)
    {
        $this->achievementService = $achievementService;
        $this->badgeService = $badgeService;
    }

    public function handle(PurchaseProcessed $event): void
    {
        $user = $event->user;

        // Check and unlock achievements
        $this->achievementService->checkAndUnlockAchievements($user);

        // Check and unlock badges
        $this->badgeService->checkAndUnlockBadges($user);
    }

    public function failed(PurchaseProcessed $event, \Throwable $exception): void
    {
        // Log the failure
        \Log::error('Failed to process loyalty rewards', [
            'user_id' => $event->user->id,
            'transaction_id' => $event->transaction->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
