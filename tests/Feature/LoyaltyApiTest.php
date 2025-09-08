<?php

namespace Tests\Feature;

use App\Models\Achievement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoyaltyApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_get_user_achievements(): void
    {
        $user = User::factory()->create();
        $achievement = Achievement::factory()->create();

        $user->achievements()->attach($achievement->id, [
            'unlocked_at' => now(),
            'progress' => 100,
        ]);

        $response = $this->getJson("/api/users/{$user->id}/achievements");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'user_id',
                    'points' => [
                        'available',
                        'total_earned',
                        'total_redeemed',
                    ],
                    'current_badge',
                    'achievements' => [
                        '*' => ['id', 'name', 'description', 'unlocked_at'],
                    ],
                ],
            ]);
    }

    public function test_admin_can_view_all_users_achievements(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $users = User::factory()->count(3)->create();

        $response = $this->actingAs($admin)
            ->getJson('/api/admin/users/achievements');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'users' => [
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
                'pagination' => [
                    'current_page',
                    'last_page',
                    'per_page',
                    'total',
                ],
            ]);
    }

    public function test_can_process_transaction(): void
    {
        $user = User::factory()->create();

        $transactionData = [
            'user_id' => $user->id,
            'amount' => 100.00,
            'type' => 'purchase',
            'description' => 'Test purchase',
        ];

        $response = $this->postJson('/api/transactions', $transactionData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'user_id',
                    'amount',
                    'points_earned',
                    'transaction_type',
                    'external_transaction_id',
                    'status',
                    'created_at',
                    'metadata',
                ],
            ]);
    }

    public function test_unauthorized_access_to_admin_endpoints(): void
    {
        $user = User::factory()->create();
        $user->assignRole('user');

        $response = $this->actingAs($user)
            ->getJson('/api/admin/users/achievements');

        $response->assertStatus(403);
    }
}
