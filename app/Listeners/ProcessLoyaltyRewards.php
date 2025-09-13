<?php

namespace App\Listeners;

use App\Events\PurchaseProcessed;
use App\Services\AchievementService;
use App\Services\BadgeService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessLoyaltyRewards implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 30;

    /**
     * The maximum number of seconds the job can run.
     */
    public int $timeout = 120;

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
            'queue_connection' => config('queue.default', 'redis'),
        ]);
    }
}
