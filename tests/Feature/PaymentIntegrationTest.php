<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PaymentIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
    }

    public function test_cashback_processing_flow(): void
    {
        $user = User::factory()->create();

        // Create some transactions for the user
        \App\Models\Transaction::factory()->count(3)->create([
            'user_id' => $user->id,
            'amount' => 100.00,
            'transaction_type' => 'purchase',
            'status' => 'completed',
        ]);

        $cashbackData = [
            'amount' => 5.00, // Within the 2% limit of ₦300 spent
            'transaction_id' => null,
        ];

        $response = $this->actingAs($user)
            ->postJson('/api/v1/cashback/process', $cashbackData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'code',
                'message',
                'data' => [
                    'item' => [
                        'amount',
                        'status',
                        'estimated_processing_time',
                        'transaction_id',
                    ],
                ],
                'errors',
                'meta',
            ]);

        // Verify cashback payment was queued
        Queue::assertPushed(\App\Jobs\ProcessCashbackPayment::class);
    }

    public function test_payment_provider_configuration(): void
    {
        $response = $this->actingAs(User::factory()->create())
            ->getJson('/api/v1/payments/configuration');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'code',
                'message',
                'data' => [
                    'item' => [
                        'providers',
                        'supported_currencies',
                        'amount_limits' => [
                            'minimum',
                            'maximum',
                        ],
                        'default_provider',
                    ],
                ],
                'errors',
                'meta',
            ]);
    }

    public function test_payment_providers_list(): void
    {
        $response = $this->actingAs(User::factory()->create())
            ->getJson('/api/v1/payments/providers');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'code',
                'message',
                'data' => [
                    'item' => [
                        'paystack' => [
                            'name',
                            'available',
                            'supported_currencies',
                            'minimum_amount',
                            'maximum_amount',
                        ],
                        'flutterwave' => [
                            'name',
                            'available',
                            'supported_currencies',
                            'minimum_amount',
                            'maximum_amount',
                        ],
                        'mock' => [
                            'name',
                            'available',
                            'supported_currencies',
                            'minimum_amount',
                            'maximum_amount',
                        ],
                    ],
                ],
                'errors',
                'meta',
            ]);
    }

    public function test_payment_initialization(): void
    {
        $user = User::factory()->create();

        $paymentData = [
            'amount' => 100.00,
            'reference' => 'test_ref_123',
            'provider' => 'mock',
        ];

        $response = $this->actingAs($user)
            ->postJson('/api/v1/payments/initialize', $paymentData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'code',
                'message',
                'data' => [
                    'item' => [
                        'success',
                        'data' => [
                            'authorization_url',
                            'access_code',
                            'reference',
                            'transaction_id',
                        ],
                        'id',
                        'amount',
                        'description',
                        'points_earned',
                        'status',
                        'created_at',
                    ],
                ],
                'errors',
                'meta',
            ]);
    }

    public function test_payment_verification(): void
    {
        $user = User::factory()->create();

        $verificationData = [
            'reference' => 'test_ref_123',
            'provider' => 'mock',
        ];

        $response = $this->actingAs($user)
            ->postJson('/api/v1/payments/verify', $verificationData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'code',
                'message',
                'data' => [
                    'item' => [
                        'success',
                        'data' => [
                            'transaction_id',
                            'status',
                            'amount',
                            'currency',
                            'reference',
                            'customer',
                            'metadata',
                        ],
                    ],
                ],
                'errors',
                'meta',
            ]);
    }

    public function test_cashback_payment_processing(): void
    {
        $user = User::factory()->create();

        $cashbackData = [
            'amount' => 25.00,
            'provider' => 'mock',
        ];

        $response = $this->actingAs($user)
            ->postJson('/api/v1/payments/cashback', $cashbackData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'code',
                'message',
                'data' => [
                    'item' => [
                        'success',
                        'data' => [
                            'transaction_id',
                            'reference',
                            'status',
                            'amount',
                            'recipient_code',
                            'processing_time',
                            'mock_provider',
                        ],
                    ],
                ],
                'errors',
                'meta',
            ]);
    }

    public function test_payment_webhook_handling(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
        ]);

        $webhookData = [
            'event' => 'charge.success',
            'data' => [
                'reference' => 'test_ref_123',
                'amount' => 10000, // Amount in kobo
                'status' => 'success',
                'customer' => [
                    'email' => 'test@example.com',
                ],
            ],
        ];

        $response = $this->actingAs($user)
            ->withHeaders([
                'x-paystack-signature' => hash_hmac('sha512', json_encode($webhookData), config('payment.providers.paystack.secret_key')),
            ])
            ->postJson('/api/v1/webhooks/payment', $webhookData);

        $response->assertStatus(200);
    }

    public function test_mock_payment_provider_behavior(): void
    {
        $paymentService = app(PaymentService::class);

        // Get mock provider
        $mockProvider = $paymentService->getProvider('mock');

        $this->assertInstanceOf(\App\Services\Payment\Providers\MockPaymentProvider::class, $mockProvider);

        // Test successful payment
        $user = User::factory()->create();
        $result = $mockProvider->initializePayment($user, 100.00, 'test_ref');

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('authorization_url', $result['data']);
    }

    public function test_payment_provider_failure_scenarios(): void
    {
        $paymentService = app(PaymentService::class);

        // Configure mock provider to fail
        $mockProvider = $paymentService->getProvider('mock');
        if ($mockProvider instanceof \App\Services\Payment\Providers\MockPaymentProvider) {
            $mockProvider->configureMockBehavior([
                'should_fail' => true,
                'failure_rate' => 1.0,
            ]);
        }

        $user = User::factory()->create();
        $result = $mockProvider->initializePayment($user, 100.00, 'test_ref');

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    public function test_payment_provider_availability(): void
    {
        $paymentService = app(PaymentService::class);

        $availableProviders = $paymentService->getAvailableProviders();

        $this->assertArrayHasKey('mock', $availableProviders);

        $mockProvider = $availableProviders['mock'];
        $this->assertTrue($mockProvider->isAvailable());
    }

    public function test_payment_limits_validation(): void
    {
        $user = User::factory()->create();

        // Test minimum amount validation
        $paymentData = [
            'amount' => 0.001, // Below minimum
            'reference' => 'test_ref_123',
            'provider' => 'mock',
        ];

        $response = $this->actingAs($user)
            ->postJson('/api/v1/payments/initialize', $paymentData);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'status',
                'code',
                'message',
                'data',
                'errors',
                'meta',
            ]);
    }
}
