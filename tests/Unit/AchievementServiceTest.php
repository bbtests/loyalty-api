<?php

namespace Tests\Unit;

use App\Events\AchievementUnlocked;
use App\Models\Achievement;
use App\Models\Transaction;
use App\Models\User;
use App\Services\AchievementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_can_check_and_unlock_first_purchase_achievement(): void
    {
        $user = User::factory()->create();

        // Create first purchase achievement
        $achievement = Achievement::factory()->create([
            'name' => 'First Purchase',
            'description' => 'Make your first purchase',
            'criteria' => ['transaction_count' => 1],
            'points_required' => 0,
            'is_active' => true,
        ]);

        // Create a transaction for the user
        Transaction::factory()->create([
            'user_id' => $user->id,
            'amount' => 100.00,
            'transaction_type' => 'purchase',
            'status' => 'completed',
        ]);

        $this->achievementService->checkAndUnlockAchievements($user);

        Event::assertDispatched(AchievementUnlocked::class, function ($event) use ($user, $achievement) {
            return $event->user->id === $user->id && $event->achievement->id === $achievement->id;
        });

        $this->assertDatabaseHas('user_achievements', [
            'user_id' => $user->id,
            'achievement_id' => $achievement->id,
        ]);
    }

    public function test_can_check_and_unlock_points_achievement(): void
    {
        $user = User::factory()->create();

        // Create loyalty points achievement
        $achievement = Achievement::factory()->create([
            'name' => 'Point Master',
            'description' => 'Earn 1000 loyalty points',
            'criteria' => ['points_minimum' => 1000],
            'points_required' => 1000,
            'is_active' => true,
        ]);

        // Create loyalty points for the user
        $user->loyaltyPoints()->create([
            'points' => 1000,
            'total_earned' => 1000,
            'total_redeemed' => 0,
        ]);

        $this->achievementService->checkAndUnlockAchievements($user);

        Event::assertDispatched(AchievementUnlocked::class, function ($event) use ($user, $achievement) {
            return $event->user->id === $user->id && $event->achievement->id === $achievement->id;
        });
    }

    public function test_can_check_and_unlock_big_spender_achievement(): void
    {
        $user = User::factory()->create();

        // Create big spender achievement
        $achievement = Achievement::factory()->create([
            'name' => 'Big Spender',
            'description' => 'Spend over ₦50,000 in a single transaction',
            'criteria' => ['single_transaction_amount' => 500],
            'points_required' => 0,
            'is_active' => true,
        ]);

        // Create a large transaction
        Transaction::factory()->create([
            'user_id' => $user->id,
            'amount' => 600.00,
            'transaction_type' => 'purchase',
            'status' => 'completed',
        ]);

        // Refresh user to load relationships
        $user->refresh();
        $user->load('transactions');

        $this->achievementService->checkAndUnlockAchievements($user);

        Event::assertDispatched(AchievementUnlocked::class, function ($event) use ($user, $achievement) {
            return $event->user->id === $user->id && $event->achievement->id === $achievement->id;
        });
    }

    public function test_does_not_unlock_achievement_twice(): void
    {
        $user = User::factory()->create();

        $achievement = Achievement::factory()->create([
            'name' => 'First Purchase',
            'description' => 'Make your first purchase',
            'criteria' => ['transaction_count' => 1],
            'points_required' => 0,
            'is_active' => true,
        ]);

        // Create a transaction
        Transaction::factory()->create([
            'user_id' => $user->id,
            'amount' => 100.00,
            'transaction_type' => 'purchase',
            'status' => 'completed',
        ]);

        // First check
        $this->achievementService->checkAndUnlockAchievements($user);

        // Second check - should not create duplicate
        $this->achievementService->checkAndUnlockAchievements($user);

        // Should only be dispatched once
        Event::assertDispatched(AchievementUnlocked::class, 1);

        // Should only have one record in database
        $this->assertDatabaseCount('user_achievements', 1);
    }

    public function test_calculates_achievement_progress_correctly(): void
    {
        $user = User::factory()->create();

        $achievement = Achievement::factory()->create([
            'name' => 'Frequent Buyer',
            'description' => 'Make 10 purchases',
            'criteria' => ['transaction_count' => 10],
            'points_required' => 0,
        ]);

        // Create 5 transactions
        Transaction::factory()->count(5)->create([
            'user_id' => $user->id,
            'amount' => 100.00,
            'transaction_type' => 'purchase',
            'status' => 'completed',
        ]);

        $progress = $this->achievementService->calculateAchievementProgress($user, $achievement);

        $this->assertEquals(50, $progress); // 5/10 = 50%
    }

    public function test_handles_multiple_criteria_achievements(): void
    {
        $user = User::factory()->create();

        $achievement = Achievement::factory()->create([
            'name' => 'VIP Customer',
            'description' => 'Make 5 purchases and earn 500 points',
            'criteria' => [
                'transaction_count' => 5,
                'points_minimum' => 500,
            ],
            'points_required' => 500,
            'is_active' => true,
        ]);

        // Create 5 transactions
        Transaction::factory()->count(5)->create([
            'user_id' => $user->id,
            'amount' => 100.00,
            'transaction_type' => 'purchase',
            'status' => 'completed',
        ]);

        // Create loyalty points
        $user->loyaltyPoints()->create([
            'points' => 500,
            'total_earned' => 500,
            'total_redeemed' => 0,
        ]);

        // Refresh user to load the loyalty points relationship
        $user->refresh();
        $user->load('loyaltyPoints');

        $this->achievementService->checkAndUnlockAchievements($user);

        Event::assertDispatched(AchievementUnlocked::class, function ($event) use ($user, $achievement) {
            return $event->user->id === $user->id && $event->achievement->id === $achievement->id;
        });
    }
}
