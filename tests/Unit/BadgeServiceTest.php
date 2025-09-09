<?php

namespace Tests\Unit;

use App\Events\BadgeUnlocked;
use App\Models\Badge;
use App\Models\Transaction;
use App\Models\User;
use App\Services\BadgeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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
        Event::fake();
    }

    public function test_check_and_unlock_badges_returns_empty_array_when_no_badges(): void
    {
        $user = User::factory()->create();

        $result = $this->badgeService->checkAndUnlockBadges($user);

        $this->assertEmpty($result);
    }

    public function test_check_and_unlock_badges_skips_already_unlocked_badges(): void
    {
        $user = User::factory()->create();
        $badge = Badge::factory()->create([
            'is_active' => true,
            'tier' => 1,
        ]);

        // User already has this badge
        $user->badges()->attach($badge->id, ['earned_at' => now()]);

        $result = $this->badgeService->checkAndUnlockBadges($user);

        $this->assertEmpty($result);
        Event::assertNotDispatched(BadgeUnlocked::class);
    }

    public function test_check_and_unlock_badges_unlocks_badge_with_points_requirement(): void
    {
        $user = User::factory()->create();
        $badge = Badge::factory()->create([
            'name' => 'Points Collector',
            'requirements' => ['points_minimum' => 1000],
            'tier' => 1,
            'is_active' => true,
        ]);

        // Create loyalty points for the user
        $user->loyaltyPoints()->create([
            'points' => 500,
            'total_earned' => 1000,
            'total_redeemed' => 0,
        ]);

        $result = $this->badgeService->checkAndUnlockBadges($user);

        $this->assertCount(1, $result);
        $this->assertEquals($badge->id, $result[0]->id);
        $this->assertDatabaseHas('user_badges', [
            'user_id' => $user->id,
            'badge_id' => $badge->id,
        ]);
        Event::assertDispatched(BadgeUnlocked::class);
    }

    public function test_check_and_unlock_badges_unlocks_badge_with_purchases_requirement(): void
    {
        $user = User::factory()->create();
        $badge = Badge::factory()->create([
            'name' => 'Frequent Shopper',
            'requirements' => ['purchases_minimum' => 5],
            'tier' => 2,
            'is_active' => true,
        ]);

        // Create 5 purchase transactions
        Transaction::factory()->count(5)->create([
            'user_id' => $user->id,
            'transaction_type' => 'purchase',
            'amount' => 50.00,
        ]);

        $result = $this->badgeService->checkAndUnlockBadges($user);

        $this->assertCount(1, $result);
        $this->assertEquals($badge->id, $result[0]->id);
        Event::assertDispatched(BadgeUnlocked::class);
    }

    public function test_check_and_unlock_badges_unlocks_badge_with_spending_requirement(): void
    {
        $user = User::factory()->create();
        $badge = Badge::factory()->create([
            'name' => 'Big Spender',
            'requirements' => ['spending_minimum' => 1000.00],
            'tier' => 3,
            'is_active' => true,
        ]);

        // Create transactions totaling more than 1000
        Transaction::factory()->create([
            'user_id' => $user->id,
            'transaction_type' => 'purchase',
            'amount' => 600.00,
        ]);
        Transaction::factory()->create([
            'user_id' => $user->id,
            'transaction_type' => 'purchase',
            'amount' => 500.00,
        ]);

        $result = $this->badgeService->checkAndUnlockBadges($user);

        $this->assertCount(1, $result);
        $this->assertEquals($badge->id, $result[0]->id);
        Event::assertDispatched(BadgeUnlocked::class);
    }

    public function test_check_and_unlock_badges_unlocks_badge_with_multiple_requirements(): void
    {
        $user = User::factory()->create();
        $badge = Badge::factory()->create([
            'name' => 'VIP Customer',
            'requirements' => [
                'points_minimum' => 500,
                'purchases_minimum' => 3,
                'spending_minimum' => 500.00,
            ],
            'tier' => 4,
            'is_active' => true,
        ]);

        // Create loyalty points
        $user->loyaltyPoints()->create([
            'points' => 300,
            'total_earned' => 500,
            'total_redeemed' => 0,
        ]);

        // Create 3 purchase transactions totaling 600
        Transaction::factory()->create([
            'user_id' => $user->id,
            'transaction_type' => 'purchase',
            'amount' => 200.00,
        ]);
        Transaction::factory()->create([
            'user_id' => $user->id,
            'transaction_type' => 'purchase',
            'amount' => 200.00,
        ]);
        Transaction::factory()->create([
            'user_id' => $user->id,
            'transaction_type' => 'purchase',
            'amount' => 200.00,
        ]);

        $result = $this->badgeService->checkAndUnlockBadges($user);

        $this->assertCount(1, $result);
        $this->assertEquals($badge->id, $result[0]->id);
        Event::assertDispatched(BadgeUnlocked::class);
    }

    public function test_check_and_unlock_badges_does_not_unlock_insufficient_requirements(): void
    {
        $user = User::factory()->create();
        $badge = Badge::factory()->create([
            'name' => 'Points Collector',
            'requirements' => ['points_minimum' => 1000],
            'tier' => 1,
            'is_active' => true,
        ]);

        // Create insufficient loyalty points
        $user->loyaltyPoints()->create([
            'points' => 500,
            'total_earned' => 500,
            'total_redeemed' => 0,
        ]);

        $result = $this->badgeService->checkAndUnlockBadges($user);

        $this->assertEmpty($result);
        Event::assertNotDispatched(BadgeUnlocked::class);
    }

    public function test_check_and_unlock_badges_skips_inactive_badges(): void
    {
        $user = User::factory()->create();
        $badge = Badge::factory()->create([
            'name' => 'Points Collector',
            'requirements' => ['points_minimum' => 1000],
            'tier' => 1,
            'is_active' => false,
        ]);

        // Create sufficient loyalty points
        $user->loyaltyPoints()->create([
            'points' => 500,
            'total_earned' => 1000,
            'total_redeemed' => 0,
        ]);

        $result = $this->badgeService->checkAndUnlockBadges($user);

        $this->assertEmpty($result);
        Event::assertNotDispatched(BadgeUnlocked::class);
    }

    public function test_check_and_unlock_badges_orders_by_tier(): void
    {
        $user = User::factory()->create();

        // Create badges with different tiers
        $tier1Badge = Badge::factory()->create([
            'name' => 'Bronze',
            'requirements' => ['points_minimum' => 100],
            'tier' => 1,
            'is_active' => true,
        ]);

        $tier3Badge = Badge::factory()->create([
            'name' => 'Gold',
            'requirements' => ['points_minimum' => 100],
            'tier' => 3,
            'is_active' => true,
        ]);

        $tier2Badge = Badge::factory()->create([
            'name' => 'Silver',
            'requirements' => ['points_minimum' => 100],
            'tier' => 2,
            'is_active' => true,
        ]);

        // Create sufficient loyalty points
        $user->loyaltyPoints()->create([
            'points' => 50,
            'total_earned' => 100,
            'total_redeemed' => 0,
        ]);

        $result = $this->badgeService->checkAndUnlockBadges($user);

        $this->assertCount(3, $result);
        // Should be ordered by tier (ascending)
        $this->assertEquals($tier1Badge->id, $result[0]->id);
        $this->assertEquals($tier2Badge->id, $result[1]->id);
        $this->assertEquals($tier3Badge->id, $result[2]->id);
    }

    public function test_check_and_unlock_badges_logs_event_to_database(): void
    {
        $user = User::factory()->create();
        $badge = Badge::factory()->create([
            'name' => 'Points Collector',
            'requirements' => ['points_minimum' => 1000],
            'tier' => 1,
            'is_active' => true,
        ]);

        // Create loyalty points for the user
        $user->loyaltyPoints()->create([
            'points' => 500,
            'total_earned' => 1000,
            'total_redeemed' => 0,
        ]);

        $this->badgeService->checkAndUnlockBadges($user);

        $this->assertDatabaseHas('events', [
            'user_id' => $user->id,
            'event_type' => 'badge_unlocked',
        ]);
    }

    public function test_check_and_unlock_badges_uses_database_transaction(): void
    {
        $user = User::factory()->create();
        $badge = Badge::factory()->create([
            'name' => 'Points Collector',
            'requirements' => ['points_minimum' => 1000],
            'tier' => 1,
            'is_active' => true,
        ]);

        // Create loyalty points for the user
        $user->loyaltyPoints()->create([
            'points' => 500,
            'total_earned' => 1000,
            'total_redeemed' => 0,
        ]);

        // Mock DB::transaction to ensure it's called
        DB::shouldReceive('transaction')
            ->once()
            ->andReturnUsing(function ($callback) {
                return $callback();
            });

        // Mock DB::table to prevent actual database calls
        DB::shouldReceive('table')
            ->with('events')
            ->andReturnSelf();
        DB::shouldReceive('insert')
            ->andReturn(true);

        $this->badgeService->checkAndUnlockBadges($user);
    }

    public function test_check_and_unlock_badges_handles_missing_requirements(): void
    {
        $user = User::factory()->create();
        $badge = Badge::factory()->create([
            'name' => 'Simple Badge',
            'requirements' => [], // No requirements
            'tier' => 1,
            'is_active' => true,
        ]);

        $result = $this->badgeService->checkAndUnlockBadges($user);

        $this->assertCount(1, $result);
        $this->assertEquals($badge->id, $result[0]->id);
        Event::assertDispatched(BadgeUnlocked::class);
    }

    public function test_check_and_unlock_badges_handles_partial_requirements(): void
    {
        $user = User::factory()->create();
        $badge = Badge::factory()->create([
            'name' => 'Partial Requirements',
            'requirements' => [
                'points_minimum' => 1000,
                'purchases_minimum' => 5,
            ],
            'tier' => 1,
            'is_active' => true,
        ]);

        // Meet only points requirement
        $user->loyaltyPoints()->create([
            'points' => 500,
            'total_earned' => 1000,
            'total_redeemed' => 0,
        ]);

        // Create only 3 purchases (need 5)
        Transaction::factory()->count(3)->create([
            'user_id' => $user->id,
            'transaction_type' => 'purchase',
            'amount' => 50.00,
        ]);

        $result = $this->badgeService->checkAndUnlockBadges($user);

        $this->assertEmpty($result);
        Event::assertNotDispatched(BadgeUnlocked::class);
    }
}
