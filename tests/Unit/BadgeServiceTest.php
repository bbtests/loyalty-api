<?php

namespace Tests\Unit;

use App\Events\BadgeUnlocked;
use App\Models\Badge;
use App\Models\User;
use App\Services\BadgeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class BadgeServiceTest extends TestCase
{
    use RefreshDatabase;

    protected BadgeService $badgeService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->badgeService = new BadgeService;
        Event::fake([BadgeUnlocked::class]);
    }

    public function test_can_check_and_unlock_bronze_badge(): void
    {
        $user = User::factory()->create();

        // Create bronze badge
        $badge = Badge::factory()->create([
            'name' => 'Bronze Member',
            'tier' => 1,
            'requirements' => ['points_minimum' => 0],
            'is_active' => true,
        ]);

        // Create loyalty points
        $user->loyaltyPoints()->create([
            'points' => 100,
            'total_earned' => 100,
            'total_redeemed' => 0,
        ]);

        // Refresh user to load the loyalty points relationship
        $user->refresh();
        $user->load('loyaltyPoints');

        $this->badgeService->checkAndUnlockBadges($user);

        Event::assertDispatched(BadgeUnlocked::class, function ($event) use ($user, $badge) {
            return $event->user->id === $user->id && $event->badge->id === $badge->id;
        });

        $this->assertDatabaseHas('user_badges', [
            'user_id' => $user->id,
            'badge_id' => $badge->id,
        ]);
    }

    public function test_can_check_and_unlock_silver_badge(): void
    {
        $user = User::factory()->create();

        // Create silver badge
        $badge = Badge::factory()->create([
            'name' => 'Silver Member',
            'tier' => 2,
            'requirements' => ['points_minimum' => 2500],
            'is_active' => true,
        ]);

        // Create loyalty points
        $user->loyaltyPoints()->create([
            'points' => 2500,
            'total_earned' => 2500,
            'total_redeemed' => 0,
        ]);

        $this->badgeService->checkAndUnlockBadges($user);

        Event::assertDispatched(BadgeUnlocked::class, function ($event) use ($user, $badge) {
            return $event->user->id === $user->id && $event->badge->id === $badge->id;
        });
    }

    public function test_can_check_and_unlock_gold_badge(): void
    {
        $user = User::factory()->create();

        // Create gold badge
        $badge = Badge::factory()->create([
            'name' => 'Gold Member',
            'tier' => 3,
            'requirements' => ['points_minimum' => 10000],
            'is_active' => true,
        ]);

        // Create loyalty points
        $user->loyaltyPoints()->create([
            'points' => 10000,
            'total_earned' => 10000,
            'total_redeemed' => 0,
        ]);

        // Refresh user to load the loyalty points relationship
        $user->refresh();
        $user->load('loyaltyPoints');

        $this->badgeService->checkAndUnlockBadges($user);

        Event::assertDispatched(BadgeUnlocked::class, function ($event) use ($user, $badge) {
            return $event->user->id === $user->id && $event->badge->id === $badge->id;
        });
    }

    public function test_can_check_and_unlock_platinum_badge(): void
    {
        $user = User::factory()->create();

        // Create platinum badge
        $badge = Badge::factory()->create([
            'name' => 'Platinum Member',
            'tier' => 4,
            'requirements' => [
                'points_minimum' => 25000,
                'purchases_minimum' => 50,
            ],
            'is_active' => true,
        ]);

        // Create loyalty points
        $user->loyaltyPoints()->create([
            'points' => 25000,
            'total_earned' => 25000,
            'total_redeemed' => 0,
        ]);

        // Create 50 transactions
        \App\Models\Transaction::factory()->count(50)->create([
            'user_id' => $user->id,
            'amount' => 100.00,
            'transaction_type' => 'purchase',
            'status' => 'completed',
        ]);

        $this->badgeService->checkAndUnlockBadges($user);

        Event::assertDispatched(BadgeUnlocked::class, function ($event) use ($user, $badge) {
            return $event->user->id === $user->id && $event->badge->id === $badge->id;
        });
    }

    public function test_does_not_unlock_badge_twice(): void
    {
        $user = User::factory()->create();

        $badge = Badge::factory()->create([
            'name' => 'Bronze Member',
            'tier' => 1,
            'requirements' => ['points_minimum' => 0],
            'is_active' => true,
        ]);

        // Create loyalty points
        $user->loyaltyPoints()->create([
            'points' => 100,
            'total_earned' => 100,
            'total_redeemed' => 0,
        ]);

        // Refresh user to load the loyalty points relationship
        $user->refresh();
        $user->load('loyaltyPoints');

        // First check
        $this->badgeService->checkAndUnlockBadges($user);

        // Second check - should not create duplicate
        $this->badgeService->checkAndUnlockBadges($user);

        // Should only be dispatched once
        Event::assertDispatched(BadgeUnlocked::class, 1);

        // Should only have one record in database
        $this->assertDatabaseCount('user_badges', 1);
    }

    public function test_unlocks_highest_eligible_badge(): void
    {
        $user = User::factory()->create();

        // Create multiple badges
        $bronze = Badge::factory()->create([
            'name' => 'Bronze Member',
            'tier' => 1,
            'requirements' => ['points_minimum' => 0],
            'is_active' => true,
        ]);

        $silver = Badge::factory()->create([
            'name' => 'Silver Member',
            'tier' => 2,
            'requirements' => ['points_minimum' => 2500],
            'is_active' => true,
        ]);

        $gold = Badge::factory()->create([
            'name' => 'Gold Member',
            'tier' => 3,
            'requirements' => ['points_minimum' => 10000],
            'is_active' => true,
        ]);

        // Create loyalty points for gold level
        $user->loyaltyPoints()->create([
            'points' => 10000,
            'total_earned' => 10000,
            'total_redeemed' => 0,
        ]);

        $this->badgeService->checkAndUnlockBadges($user);

        // Should unlock all eligible badges (bronze, silver, gold)
        Event::assertDispatched(BadgeUnlocked::class, 3);

        $this->assertDatabaseHas('user_badges', [
            'user_id' => $user->id,
            'badge_id' => $bronze->id,
        ]);

        $this->assertDatabaseHas('user_badges', [
            'user_id' => $user->id,
            'badge_id' => $silver->id,
        ]);

        $this->assertDatabaseHas('user_badges', [
            'user_id' => $user->id,
            'badge_id' => $gold->id,
        ]);
    }

    public function test_calculates_badge_progress_correctly(): void
    {
        $user = User::factory()->create();

        $badge = Badge::factory()->create([
            'name' => 'Silver Member',
            'tier' => 2,
            'requirements' => ['points_minimum' => 2500],
            'is_active' => true,
        ]);

        // Create loyalty points (halfway to requirement)
        $user->loyaltyPoints()->create([
            'points' => 1250,
            'total_earned' => 1250,
            'total_redeemed' => 0,
        ]);

        $progress = $this->badgeService->calculateBadgeProgress($user, $badge);

        $this->assertEquals(50, $progress); // 1250/2500 = 50%
    }

    public function test_handles_multiple_criteria_badges(): void
    {
        $user = User::factory()->create();

        $badge = Badge::factory()->create([
            'name' => 'VIP Member',
            'tier' => 5,
            'requirements' => [
                'points_minimum' => 50000,
                'purchases_minimum' => 100,
            ],
            'is_active' => true,
        ]);

        // Create loyalty points
        $user->loyaltyPoints()->create([
            'points' => 50000,
            'total_earned' => 50000,
            'total_redeemed' => 0,
        ]);

        // Create 100 transactions
        \App\Models\Transaction::factory()->count(100)->create([
            'user_id' => $user->id,
            'amount' => 100.00,
            'transaction_type' => 'purchase',
            'status' => 'completed',
        ]);

        // Refresh user to load the loyalty points relationship
        $user->refresh();
        $user->load('loyaltyPoints');

        $this->badgeService->checkAndUnlockBadges($user);

        Event::assertDispatched(BadgeUnlocked::class, function ($event) use ($user, $badge) {
            return $event->user->id === $user->id && $event->badge->id === $badge->id;
        });
    }
}
