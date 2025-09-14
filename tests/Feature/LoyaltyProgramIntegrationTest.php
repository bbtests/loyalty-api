<?php

namespace Tests\Feature;

use App\Events\AchievementUnlocked;
use App\Events\BadgeUnlocked;
use App\Models\Achievement;
use App\Models\Badge;
use App\Models\Transaction;
use App\Models\User;
use App\Services\LoyaltyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class LoyaltyProgramIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Event::fake();
        // Don't fake the queue for loyalty rewards processing
        // Queue::fake();
    }

    public function test_complete_loyalty_program_flow(): void
    {
        // Create user
        $user = User::factory()->create();

        // Create achievements
        $firstPurchase = Achievement::factory()->create([
            'name' => 'First Purchase',
            'description' => 'Make your first purchase',
            'criteria' => ['transaction_count' => 1],
            'points_required' => 0,
            'is_active' => true,
        ]);

        $pointMaster = Achievement::factory()->create([
            'name' => 'Point Master',
            'description' => 'Earn 1000 loyalty points',
            'criteria' => ['points_minimum' => 1000],
            'points_required' => 1000,
            'is_active' => true,
        ]);

        // Create badges
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

        // Process first purchase
        Queue::fake();

        $loyaltyService = app(LoyaltyService::class);
        $transaction = $loyaltyService->processPurchase($user, 100.00);

        // Verify job was dispatched
        Queue::assertPushed(\App\Jobs\ProcessPurchaseEvent::class, function ($job) use ($user, $transaction) {
            return $job->user_id === $user->id && $job->transaction_id === $transaction->id;
        });

        // Manually process the loyalty rewards
        $achievementService = app(\App\Services\AchievementService::class);
        $badgeService = app(\App\Services\BadgeService::class);
        $achievementService->checkAndUnlockAchievements($user);
        $badgeService->checkAndUnlockBadges($user);

        // Verify transaction was created
        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'amount' => 100.00,
            'transaction_type' => 'purchase',
            'status' => 'completed',
        ]);

        // Verify loyalty points were awarded
        $this->assertDatabaseHas('loyalty_points', [
            'user_id' => $user->id,
            'points' => 1000, // 100 * 10 points per naira
            'total_earned' => 1000,
        ]);

        // Verify events were dispatched
        Event::assertDispatched(AchievementUnlocked::class, function ($event) use ($user, $firstPurchase) {
            return $event->user->id === $user->id && $event->achievement->id === $firstPurchase->id;
        });

        Event::assertDispatched(BadgeUnlocked::class, function ($event) use ($user, $bronze) {
            return $event->user->id === $user->id && $event->badge->id === $bronze->id;
        });
    }

    public function test_multiple_purchases_progression(): void
    {
        $user = User::factory()->create();

        // Create achievements
        $frequentBuyer = Achievement::factory()->create([
            'name' => 'Frequent Buyer',
            'description' => 'Make 5 purchases',
            'criteria' => ['transaction_count' => 5],
            'points_required' => 0,
            'is_active' => true,
        ]);

        // Create badges
        $silver = Badge::factory()->create([
            'name' => 'Silver Member',
            'tier' => 2,
            'requirements' => ['points_minimum' => 2500],
            'is_active' => true,
        ]);

        $loyaltyService = app(LoyaltyService::class);

        // Process 5 purchases
        for ($i = 1; $i <= 5; $i++) {
            $transaction = $loyaltyService->processPurchase($user, 100.00);

            $this->assertDatabaseHas('transactions', [
                'user_id' => $user->id,
                'amount' => 100.00,
                'transaction_type' => 'purchase',
                'status' => 'completed',
            ]);
        }

        // Manually process the loyalty rewards after all purchases
        $achievementService = app(\App\Services\AchievementService::class);
        $badgeService = app(\App\Services\BadgeService::class);
        $achievementService->checkAndUnlockAchievements($user);
        $badgeService->checkAndUnlockBadges($user);

        // Verify total points
        $loyaltyPoints = $user->fresh()->loyaltyPoints;
        $this->assertEquals(5000, $loyaltyPoints->points); // 5 * 100 * 10

        // Verify achievement was unlocked
        Event::assertDispatched(AchievementUnlocked::class, function ($event) use ($user, $frequentBuyer) {
            return $event->user->id === $user->id && $event->achievement->id === $frequentBuyer->id;
        });

        // Verify badge was unlocked
        Event::assertDispatched(BadgeUnlocked::class, function ($event) use ($user, $silver) {
            return $event->user->id === $user->id && $event->badge->id === $silver->id;
        });
    }

    public function test_points_redemption_flow(): void
    {
        $user = User::factory()->create();

        $loyaltyService = app(LoyaltyService::class);

        // Process purchase to earn points
        $loyaltyService->processPurchase($user, 100.00);

        // Verify points were earned
        $loyaltyPoints = $user->fresh()->loyaltyPoints;
        $this->assertEquals(1000, $loyaltyPoints->points);

        // Redeem points
        $result = $loyaltyService->redeemPoints($user, 500);

        $this->assertTrue($result);

        // Verify points were redeemed
        $loyaltyPoints = $user->fresh()->loyaltyPoints;
        $this->assertEquals(500, $loyaltyPoints->points);
        $this->assertEquals(500, $loyaltyPoints->total_redeemed);
    }

    public function test_insufficient_points_redemption(): void
    {
        $user = User::factory()->create();

        $loyaltyService = app(LoyaltyService::class);

        // Process purchase to earn points
        $loyaltyService->processPurchase($user, 100.00);

        // Try to redeem more points than available
        $result = $loyaltyService->redeemPoints($user, 2000);

        $this->assertFalse($result);

        // Verify points were not redeemed
        $loyaltyPoints = $user->fresh()->loyaltyPoints;
        $this->assertEquals(1000, $loyaltyPoints->points);
        $this->assertEquals(0, $loyaltyPoints->total_redeemed);
    }

    public function test_message_queue_integration(): void
    {
        $user = User::factory()->create();

        Queue::fake();

        $loyaltyService = app(LoyaltyService::class);
        $transaction = $loyaltyService->processPurchase($user, 100.00);

        // Verify job was dispatched
        Queue::assertPushed(\App\Jobs\ProcessPurchaseEvent::class, function ($job) use ($user, $transaction) {
            return $job->user_id === $user->id && $job->transaction_id === $transaction->id;
        });

        // Verify transaction was created
        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'amount' => 100.00,
            'transaction_type' => 'purchase',
            'status' => 'completed',
        ]);
    }

    public function test_admin_achievements_endpoint(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $user = User::factory()->create();

        // Create achievement and assign to user
        $achievement = Achievement::factory()->create();
        $user->achievements()->attach($achievement->id, [
            'unlocked_at' => now(),
            'progress' => 100,
        ]);

        $response = $this->actingAs($admin)
            ->getJson('/api/v1/admin/users/achievements');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'code',
                'message',
                'data' => [
                    'items' => [
                        '*' => [
                            'id',
                            'name',
                            'email',
                            'points' => [
                                'available',
                                'total_earned',
                            ],
                            'achievements_count',
                            'current_badge',
                            'member_since',
                            'last_activity',
                        ],
                    ],
                ],
                'errors',
                'meta' => [
                    'pagination' => [
                        'current_page',
                        'last_page',
                        'per_page',
                        'total',
                    ],
                ],
            ]);
    }

    public function test_user_achievements_endpoint(): void
    {
        $user = User::factory()->create();

        // Create achievement and assign to user
        $achievement = Achievement::factory()->create();
        $user->achievements()->attach($achievement->id, [
            'unlocked_at' => now(),
            'progress' => 100,
        ]);

        $response = $this->actingAs($user)->getJson("/api/v1/users/{$user->id}/achievements");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'code',
                'message',
                'data' => [
                    'item' => [
                        'user_id',
                        'points' => [
                            'available',
                            'total_earned',
                            'total_redeemed',
                        ],
                        'achievements' => [
                            '*' => [
                                'id',
                                'name',
                                'description',
                                'unlocked_at',
                            ],
                        ],
                        'badges' => [
                            '*' => [
                                'id',
                                'name',
                                'description',
                                'tier',
                                'earned_at',
                            ],
                        ],
                        'current_badge',
                    ],
                ],
                'errors',
                'meta',
            ]);
    }

    public function test_transaction_processing_endpoint(): void
    {
        $user = User::factory()->create();

        $transactionData = [
            'user_id' => $user->id,
            'amount' => 100.00,
            'external_transaction_id' => 'ext_123',
        ];

        $response = $this->actingAs($user)->postJson('/api/v1/transactions', $transactionData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'status',
                'code',
                'message',
                'data' => [
                    'item' => [
                        'id',
                        'user_id',
                        'amount',
                        'points_earned',
                        'transaction_type',
                        'status',
                        'created_at',
                    ],
                ],
                'errors',
                'meta',
            ]);

        // Verify transaction was created
        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'amount' => 100.00,
            'external_transaction_id' => 'ext_123',
            'transaction_type' => 'purchase',
            'status' => 'completed',
        ]);

        // Verify loyalty points were awarded
        $this->assertDatabaseHas('loyalty_points', [
            'user_id' => $user->id,
            'points' => 1000,
            'total_earned' => 1000,
        ]);
    }
}
