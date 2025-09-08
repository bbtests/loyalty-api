<?php

namespace Tests\Unit;

use App\Events\AchievementUnlocked;
use App\Models\Achievement;
use App\Models\User;
use App\Services\LoyaltyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class LoyaltyServiceTest extends TestCase
{
    use RefreshDatabase;

    protected LoyaltyService $loyaltyService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->loyaltyService = new LoyaltyService;
        Event::fake();
    }

    public function test_can_award_points_to_user(): void
    {
        $user = User::factory()->create();

        $this->loyaltyService->awardPoints($user, 100);

        $this->assertDatabaseHas('loyalty_points', [
            'user_id' => $user->id,
            'points' => 100,
        ]);
    }

    public function test_can_calculate_user_total_points(): void
    {
        $user = User::factory()->create();

        // Award multiple point transactions
        $this->loyaltyService->awardPoints($user, 100);
        $this->loyaltyService->awardPoints($user, 50);
        $this->loyaltyService->redeemPoints($user, 25);

        $totalPoints = $this->loyaltyService->getUserTotalPoints($user);

        $this->assertEquals(125, $totalPoints);
    }

    public function test_triggers_achievement_unlock_event(): void
    {
        $user = User::factory()->create();
        Achievement::factory()->create([
            'name' => 'First Purchase',
            'description' => 'Make your first purchase',
            'criteria' => json_encode(['transaction_count' => 1]),
            'points_required' => 0,
        ]);

        // Create a transaction for the user to meet the criteria
        \App\Models\Transaction::factory()->create([
            'user_id' => $user->id,
            'amount' => 100.00,
            'transaction_type' => 'purchase',
            'status' => 'completed',
        ]);

        $this->loyaltyService->checkAchievements($user);

        Event::assertDispatched(AchievementUnlocked::class);
    }

    public function test_can_redeem_points(): void
    {
        $user = User::factory()->create();
        $this->loyaltyService->awardPoints($user, 1000);

        $result = $this->loyaltyService->redeemPoints($user, 500);

        $this->assertTrue($result);
        $this->assertEquals(500, $this->loyaltyService->getUserTotalPoints($user));
    }

    public function test_cannot_redeem_insufficient_points(): void
    {
        $user = User::factory()->create();
        $this->loyaltyService->awardPoints($user, 100);

        $result = $this->loyaltyService->redeemPoints($user, 500);

        $this->assertFalse($result);
        $this->assertEquals(100, $this->loyaltyService->getUserTotalPoints($user));
    }
}
