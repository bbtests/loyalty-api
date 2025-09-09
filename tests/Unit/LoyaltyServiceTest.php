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
            'criteria' => ['transaction_count' => 1],
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

    public function test_can_process_purchase(): void
    {
        $user = User::factory()->create();
        $amount = 100.00;
        $externalTransactionId = 'ext_123';

        $transaction = $this->loyaltyService->processPurchase($user, $amount, $externalTransactionId);

        $this->assertInstanceOf(\App\Models\Transaction::class, $transaction);
        $this->assertEquals($user->id, $transaction->user_id);
        $this->assertEquals($amount, $transaction->amount);
        $this->assertEquals('purchase', $transaction->transaction_type);
        $this->assertEquals($externalTransactionId, $transaction->external_transaction_id);
        $this->assertEquals('completed', $transaction->status);

        // Check that points were calculated correctly (default 10 points per dollar)
        $expectedPoints = (int) floor($amount * 10);
        $this->assertEquals($expectedPoints, $transaction->points_earned);

        // Check that loyalty points were created/updated
        $this->assertDatabaseHas('loyalty_points', [
            'user_id' => $user->id,
            'points' => $expectedPoints,
            'total_earned' => $expectedPoints,
        ]);

        // Refresh the user to get updated loyalty points
        $user->refresh();
        $this->assertNotNull($user->loyaltyPoints);
        $this->assertEquals($expectedPoints, $user->loyaltyPoints->points);
        $this->assertEquals($expectedPoints, $user->loyaltyPoints->total_earned);
    }

    public function test_process_purchase_without_external_transaction_id(): void
    {
        $user = User::factory()->create();
        $amount = 50.00;

        $transaction = $this->loyaltyService->processPurchase($user, $amount);

        $this->assertInstanceOf(\App\Models\Transaction::class, $transaction);
        $this->assertNull($transaction->external_transaction_id);
    }

    public function test_process_purchase_dispatches_event(): void
    {
        $user = User::factory()->create();
        $amount = 100.00;

        $this->loyaltyService->processPurchase($user, $amount);

        Event::assertDispatched(\App\Events\PurchaseProcessed::class);
    }

    public function test_process_purchase_uses_database_transaction(): void
    {
        $user = User::factory()->create();
        $amount = 100.00;

        // Mock DB::transaction to ensure it's called
        \Illuminate\Support\Facades\DB::shouldReceive('transaction')
            ->once()
            ->andReturnUsing(function ($callback) {
                return $callback();
            });

        $this->loyaltyService->processPurchase($user, $amount);
    }

    public function test_can_get_user_loyalty_data(): void
    {
        $user = User::factory()->create();

        // Create loyalty points
        $user->loyaltyPoints()->create([
            'points' => 500,
            'total_earned' => 1000,
            'total_redeemed' => 500,
        ]);

        // Create achievements
        $achievement = Achievement::factory()->create([
            'name' => 'Test Achievement',
            'description' => 'Test Description',
            'badge_icon' => 'test-icon',
        ]);
        $user->achievements()->attach($achievement->id, ['unlocked_at' => now()]);

        // Create badges
        $badge = \App\Models\Badge::factory()->create([
            'name' => 'Test Badge',
            'description' => 'Test Badge Description',
            'icon' => 'badge-icon',
            'tier' => 1,
        ]);
        $user->badges()->attach($badge->id, ['earned_at' => now()]);

        $data = $this->loyaltyService->getUserLoyaltyData($user);

        $this->assertEquals($user->id, $data['user_id']);

        // Check points data
        $this->assertEquals(500, $data['points']['available']);
        $this->assertEquals(1000, $data['points']['total_earned']);
        $this->assertEquals(500, $data['points']['total_redeemed']);

        // Check achievements data
        $this->assertCount(1, $data['achievements']);
        $this->assertEquals($achievement->id, $data['achievements'][0]['id']);
        $this->assertEquals('Test Achievement', $data['achievements'][0]['name']);

        // Check badges data
        $this->assertCount(1, $data['badges']);
        $this->assertEquals($badge->id, $data['badges'][0]['id']);
        $this->assertEquals('Test Badge', $data['badges'][0]['name']);
        $this->assertEquals(1, $data['badges'][0]['tier']);

        // Check current badge
        $this->assertNotNull($data['current_badge']);
    }

    public function test_get_user_loyalty_data_with_no_loyalty_points(): void
    {
        $user = User::factory()->create();

        $data = $this->loyaltyService->getUserLoyaltyData($user);

        $this->assertEquals(0, $data['points']['available']);
        $this->assertEquals(0, $data['points']['total_earned']);
        $this->assertEquals(0, $data['points']['total_redeemed']);
        $this->assertEmpty($data['achievements']);
        $this->assertEmpty($data['badges']);
        $this->assertNull($data['current_badge']);
    }

    public function test_can_check_achievements(): void
    {
        $user = User::factory()->create();

        // Create an achievement with criteria
        $achievement = Achievement::factory()->create([
            'name' => 'Transaction Master',
            'is_active' => true,
            'criteria' => ['transaction_count' => 2],
        ]);

        // Create transactions to meet criteria
        \App\Models\Transaction::factory()->count(2)->create([
            'user_id' => $user->id,
            'transaction_type' => 'purchase',
        ]);

        $this->loyaltyService->checkAchievements($user);

        // Check that achievement was unlocked
        $this->assertDatabaseHas('user_achievements', [
            'user_id' => $user->id,
            'achievement_id' => $achievement->id,
        ]);

        Event::assertDispatched(AchievementUnlocked::class);
    }

    public function test_check_achievements_skips_already_unlocked(): void
    {
        $user = User::factory()->create();

        $achievement = Achievement::factory()->create([
            'name' => 'Transaction Master',
            'is_active' => true,
            'criteria' => ['transaction_count' => 1],
        ]);

        // User already has this achievement
        $user->achievements()->attach($achievement->id, ['unlocked_at' => now()]);

        $this->loyaltyService->checkAchievements($user);

        // Should not dispatch event again
        Event::assertNotDispatched(AchievementUnlocked::class);
    }

    public function test_check_achievements_skips_inactive_achievements(): void
    {
        $user = User::factory()->create();

        $achievement = Achievement::factory()->create([
            'name' => 'Transaction Master',
            'is_active' => false,
            'criteria' => ['transaction_count' => 1],
        ]);

        \App\Models\Transaction::factory()->create([
            'user_id' => $user->id,
            'transaction_type' => 'purchase',
        ]);

        $this->loyaltyService->checkAchievements($user);

        Event::assertNotDispatched(AchievementUnlocked::class);
    }

    public function test_check_achievements_handles_null_criteria(): void
    {
        $user = User::factory()->create();

        $achievement = Achievement::factory()->create([
            'name' => 'No Criteria',
            'is_active' => true,
            'criteria' => null,
        ]);

        $this->loyaltyService->checkAchievements($user);

        Event::assertNotDispatched(AchievementUnlocked::class);
    }

    public function test_check_achievements_handles_empty_criteria(): void
    {
        $user = User::factory()->create();

        $achievement = Achievement::factory()->create([
            'name' => 'Empty Criteria',
            'is_active' => true,
            'criteria' => [],
        ]);

        $this->loyaltyService->checkAchievements($user);

        Event::assertNotDispatched(AchievementUnlocked::class);
    }

    public function test_check_achievements_handles_points_earned_criteria(): void
    {
        $user = User::factory()->create();

        $achievement = Achievement::factory()->create([
            'name' => 'Points Master',
            'is_active' => true,
            'criteria' => ['points_earned' => 500],
        ]);

        // Create loyalty points
        $user->loyaltyPoints()->create([
            'points' => 300,
            'total_earned' => 500,
            'total_redeemed' => 0,
        ]);

        $this->loyaltyService->checkAchievements($user);

        $this->assertDatabaseHas('user_achievements', [
            'user_id' => $user->id,
            'achievement_id' => $achievement->id,
        ]);

        Event::assertDispatched(AchievementUnlocked::class);
    }
}
