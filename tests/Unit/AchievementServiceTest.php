<?php

namespace Tests\Unit;

use App\Events\AchievementUnlocked;
use App\Models\Achievement;
use App\Models\Transaction;
use App\Models\User;
use App\Services\AchievementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class AchievementServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AchievementService $achievementService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->achievementService = new AchievementService;
        Event::fake();
    }

    public function test_check_and_unlock_achievements_returns_empty_array_when_no_achievements(): void
    {
        $user = User::factory()->create();

        $result = $this->achievementService->checkAndUnlockAchievements($user);

        $this->assertEmpty($result);
    }

    public function test_check_and_unlock_achievements_skips_already_unlocked_achievements(): void
    {
        $user = User::factory()->create();
        $achievement = Achievement::factory()->create([
            'name' => 'First Purchase',
            'is_active' => true,
        ]);

        // User already has this achievement
        $user->achievements()->attach($achievement->id, ['unlocked_at' => now()]);

        $result = $this->achievementService->checkAndUnlockAchievements($user);

        $this->assertEmpty($result);
        Event::assertNotDispatched(AchievementUnlocked::class);
    }

    public function test_check_and_unlock_achievements_unlocks_first_purchase_achievement(): void
    {
        $user = User::factory()->create();
        $achievement = Achievement::factory()->create([
            'name' => 'First Purchase',
            'is_active' => true,
        ]);

        // Create a purchase transaction
        Transaction::factory()->create([
            'user_id' => $user->id,
            'transaction_type' => 'purchase',
            'amount' => 100.00,
        ]);

        $result = $this->achievementService->checkAndUnlockAchievements($user);

        $this->assertCount(1, $result);
        $this->assertEquals($achievement->id, $result[0]->id);
        $this->assertDatabaseHas('user_achievements', [
            'user_id' => $user->id,
            'achievement_id' => $achievement->id,
        ]);
        Event::assertDispatched(AchievementUnlocked::class);
    }

    public function test_check_and_unlock_achievements_unlocks_loyal_customer_achievement(): void
    {
        $user = User::factory()->create();
        $achievement = Achievement::factory()->create([
            'name' => 'Loyal Customer',
            'points_required' => 1000,
            'is_active' => true,
        ]);

        // Create loyalty points for the user
        $user->loyaltyPoints()->create([
            'points' => 500,
            'total_earned' => 1000,
            'total_redeemed' => 0,
        ]);

        $result = $this->achievementService->checkAndUnlockAchievements($user);

        $this->assertCount(1, $result);
        $this->assertEquals($achievement->id, $result[0]->id);
        Event::assertDispatched(AchievementUnlocked::class);
    }

    public function test_check_and_unlock_achievements_unlocks_point_master_achievement(): void
    {
        $user = User::factory()->create();
        $achievement = Achievement::factory()->create([
            'name' => 'Point Master',
            'points_required' => 5000,
            'is_active' => true,
        ]);

        // Create loyalty points for the user
        $user->loyaltyPoints()->create([
            'points' => 2000,
            'total_earned' => 5000,
            'total_redeemed' => 0,
        ]);

        $result = $this->achievementService->checkAndUnlockAchievements($user);

        $this->assertCount(1, $result);
        $this->assertEquals($achievement->id, $result[0]->id);
        Event::assertDispatched(AchievementUnlocked::class);
    }

    public function test_check_and_unlock_achievements_unlocks_big_spender_achievement(): void
    {
        $user = User::factory()->create();
        $achievement = Achievement::factory()->create([
            'name' => 'Big Spender',
            'is_active' => true,
        ]);

        // Create a large purchase transaction
        Transaction::factory()->create([
            'user_id' => $user->id,
            'transaction_type' => 'purchase',
            'amount' => 600.00,
        ]);

        $result = $this->achievementService->checkAndUnlockAchievements($user);

        $this->assertCount(1, $result);
        $this->assertEquals($achievement->id, $result[0]->id);
        Event::assertDispatched(AchievementUnlocked::class);
    }

    public function test_check_and_unlock_achievements_unlocks_frequent_buyer_achievement(): void
    {
        $user = User::factory()->create();
        $achievement = Achievement::factory()->create([
            'name' => 'Frequent Buyer',
            'is_active' => true,
        ]);

        // Create 10 purchase transactions
        Transaction::factory()->count(10)->create([
            'user_id' => $user->id,
            'transaction_type' => 'purchase',
            'amount' => 50.00,
        ]);

        $result = $this->achievementService->checkAndUnlockAchievements($user);

        $this->assertCount(1, $result);
        $this->assertEquals($achievement->id, $result[0]->id);
        Event::assertDispatched(AchievementUnlocked::class);
    }

    public function test_check_and_unlock_achievements_does_not_unlock_insufficient_criteria(): void
    {
        $user = User::factory()->create();
        $achievement = Achievement::factory()->create([
            'name' => 'Loyal Customer',
            'points_required' => 1000,
            'is_active' => true,
        ]);

        // Create insufficient loyalty points
        $user->loyaltyPoints()->create([
            'points' => 500,
            'total_earned' => 500,
            'total_redeemed' => 0,
        ]);

        $result = $this->achievementService->checkAndUnlockAchievements($user);

        $this->assertEmpty($result);
        Event::assertNotDispatched(AchievementUnlocked::class);
    }

    public function test_check_and_unlock_achievements_skips_inactive_achievements(): void
    {
        $user = User::factory()->create();
        $achievement = Achievement::factory()->create([
            'name' => 'First Purchase',
            'is_active' => false,
        ]);

        // Create a purchase transaction
        Transaction::factory()->create([
            'user_id' => $user->id,
            'transaction_type' => 'purchase',
            'amount' => 100.00,
        ]);

        $result = $this->achievementService->checkAndUnlockAchievements($user);

        $this->assertEmpty($result);
        Event::assertNotDispatched(AchievementUnlocked::class);
    }

    public function test_check_and_unlock_achievements_handles_unknown_achievement_type(): void
    {
        $user = User::factory()->create();
        $achievement = Achievement::factory()->create([
            'name' => 'Unknown Achievement',
            'is_active' => true,
        ]);

        $result = $this->achievementService->checkAndUnlockAchievements($user);

        $this->assertEmpty($result);
        Event::assertNotDispatched(AchievementUnlocked::class);
    }

    public function test_check_and_unlock_achievements_logs_event_to_database(): void
    {
        $user = User::factory()->create();
        $achievement = Achievement::factory()->create([
            'name' => 'First Purchase',
            'is_active' => true,
        ]);

        // Create a purchase transaction
        Transaction::factory()->create([
            'user_id' => $user->id,
            'transaction_type' => 'purchase',
            'amount' => 100.00,
        ]);

        $this->achievementService->checkAndUnlockAchievements($user);

        $this->assertDatabaseHas('events', [
            'user_id' => $user->id,
            'event_type' => 'achievement_unlocked',
        ]);
    }

    public function test_check_and_unlock_achievements_unlocks_multiple_achievements(): void
    {
        $user = User::factory()->create();

        // Create multiple achievements
        $firstPurchase = Achievement::factory()->create([
            'name' => 'First Purchase',
            'is_active' => true,
        ]);

        $loyalCustomer = Achievement::factory()->create([
            'name' => 'Loyal Customer',
            'points_required' => 1000,
            'is_active' => true,
        ]);

        // Create conditions for both achievements
        Transaction::factory()->create([
            'user_id' => $user->id,
            'transaction_type' => 'purchase',
            'amount' => 100.00,
        ]);

        $user->loyaltyPoints()->create([
            'points' => 500,
            'total_earned' => 1000,
            'total_redeemed' => 0,
        ]);

        $result = $this->achievementService->checkAndUnlockAchievements($user);

        $this->assertCount(2, $result);
        Event::assertDispatchedTimes(AchievementUnlocked::class, 2);
    }

    public function test_check_and_unlock_achievements_uses_database_transaction(): void
    {
        $user = User::factory()->create();
        $achievement = Achievement::factory()->create([
            'name' => 'First Purchase',
            'is_active' => true,
        ]);

        // Create a purchase transaction
        Transaction::factory()->create([
            'user_id' => $user->id,
            'transaction_type' => 'purchase',
            'amount' => 100.00,
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

        $this->achievementService->checkAndUnlockAchievements($user);
    }
}
