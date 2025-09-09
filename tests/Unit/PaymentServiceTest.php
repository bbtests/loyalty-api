<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    protected PaymentService $paymentService;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Set up test configuration
        Config::set('payment.default_provider', 'paystack');
        Config::set('payment.providers.paystack.secret_key', 'sk_test_secret');
        Config::set('payment.providers.paystack.public_key', 'pk_test_public');
        Config::set('payment.providers.paystack.base_url', 'https://api.paystack.co');
        Config::set('payment.providers.flutterwave.secret_key', 'flw_test_secret');
        Config::set('payment.providers.flutterwave.public_key', 'flw_test_public');
        Config::set('payment.providers.flutterwave.base_url', 'https://api.flutterwave.com/v3');
        Config::set('payment.providers.mock.secret_key', 'mock_secret');
        Config::set('payment.providers.mock.public_key', 'mock_public');
        Config::set('payment.providers.mock.base_url', 'https://mock.api.com');
        Config::set('app.url', 'http://localhost');

        $this->paymentService = new PaymentService;
        $this->user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
    }

    public function test_get_public_key_returns_correct_key(): void
    {
        $publicKey = $this->paymentService->getPublicKey();

        $this->assertEquals('pk_test_public', $publicKey);
    }

    public function test_process_cashback_with_paystack_provider(): void
    {
        Config::set('payment.default_provider', 'paystack');
        $paymentService = new PaymentService;

        Http::fake([
            'api.paystack.co/transferrecipient' => Http::response([
                'status' => true,
                'data' => [
                    'recipient_code' => 'RCP_test123',
                ],
            ], 200),
            'api.paystack.co/transfer' => Http::response([
                'status' => true,
                'data' => [
                    'transfer_code' => 'TRF_test123',
                    'reference' => 'cashback_test123',
                ],
            ], 200),
        ]);

        $result = $paymentService->processCashback($this->user, 100.00);

        $this->assertEquals('completed', $result['status']);
        $this->assertEquals('TRF_test123', $result['transaction_id']);
        $this->assertEquals('cashback_test123', $result['reference']);
    }

    public function test_process_cashback_with_flutterwave_provider(): void
    {
        Config::set('payment.default_provider', 'flutterwave');
        $paymentService = new PaymentService;

        Http::fake([
            'api.flutterwave.com/v3/transfers' => Http::response([
                'status' => 'success',
                'data' => [
                    'id' => 12345,
                    'reference' => 'fw_cashback_test123',
                ],
            ], 200),
        ]);

        $result = $paymentService->processCashback($this->user, 100.00);

        $this->assertEquals('pending', $result['status']);
        $this->assertEquals(12345, $result['transaction_id']);
        $this->assertEquals('fw_cashback_test123', $result['reference']);
    }

    public function test_process_cashback_with_mock_provider(): void
    {
        Config::set('payment.default_provider', 'mock');
        $paymentService = new PaymentService;

        $result = $paymentService->processCashback($this->user, 100.00);

        $this->assertContains($result['status'], ['completed', 'pending', 'failed']);
        if ($result['status'] !== 'failed') {
            $this->assertArrayHasKey('transaction_id', $result);
            $this->assertArrayHasKey('reference', $result);
        }
    }

    public function test_process_cashback_handles_paystack_failure(): void
    {
        Config::set('payment.default_provider', 'paystack');
        $paymentService = new PaymentService;

        Http::fake([
            'api.paystack.co/transferrecipient' => Http::response([
                'status' => false,
                'message' => 'Invalid account details',
            ], 400),
        ]);

        $result = $paymentService->processCashback($this->user, 100.00);

        $this->assertEquals('failed', $result['status']);
        $this->assertArrayHasKey('error', $result);
    }

    public function test_process_cashback_uses_cached_recipient(): void
    {
        Config::set('payment.default_provider', 'paystack');
        $paymentService = new PaymentService;

        // Cache a recipient code
        Cache::put('paystack_recipient_'.$this->user->id, 'RCP_cached123', 86400);

        Http::fake([
            'api.paystack.co/transfer' => Http::response([
                'status' => true,
                'data' => [
                    'transfer_code' => 'TRF_test123',
                    'reference' => 'cashback_test123',
                ],
            ], 200),
        ]);

        $result = $paymentService->processCashback($this->user, 100.00);

        $this->assertEquals('completed', $result['status']);

        // Should not call transferrecipient endpoint since we have cached recipient
        Http::assertNotSent(function ($request) {
            return str_contains($request->url(), 'transferrecipient');
        });
    }

    public function test_initialize_payment_with_paystack(): void
    {
        Config::set('payment.default_provider', 'paystack');
        $paymentService = new PaymentService;

        Http::fake([
            'api.paystack.co/transaction/initialize' => Http::response([
                'status' => true,
                'data' => [
                    'authorization_url' => 'https://checkout.paystack.com/test123',
                    'access_code' => 'access_test123',
                    'reference' => 'ref_test123',
                ],
            ], 200),
        ]);

        $result = $paymentService->initializePayment($this->user, 100.00, 'ref_test123');

        $this->assertEquals('success', $result['status']);
        $this->assertEquals('https://checkout.paystack.com/test123', $result['authorization_url']);
        $this->assertEquals('access_test123', $result['access_code']);
        $this->assertEquals('ref_test123', $result['reference']);
    }

    public function test_initialize_payment_with_flutterwave(): void
    {
        Config::set('payment.default_provider', 'flutterwave');
        $paymentService = new PaymentService;

        Http::fake([
            'api.flutterwave.com/v3/payments' => Http::response([
                'status' => 'success',
                'data' => [
                    'link' => 'https://checkout.flutterwave.com/test123',
                ],
            ], 200),
        ]);

        $result = $paymentService->initializePayment($this->user, 100.00, 'ref_test123');

        $this->assertEquals('success', $result['status']);
        $this->assertEquals('https://checkout.flutterwave.com/test123', $result['authorization_url']);
        $this->assertEquals('ref_test123', $result['reference']);
    }

    public function test_initialize_payment_with_mock(): void
    {
        Config::set('payment.default_provider', 'mock');
        $paymentService = new PaymentService;

        $result = $paymentService->initializePayment($this->user, 100.00, 'ref_test123');

        $this->assertEquals('success', $result['status']);
        $this->assertStringContainsString('ref_test123', $result['authorization_url']);
        $this->assertEquals('ref_test123', $result['reference']);
        $this->assertTrue($result['mock']);
    }

    public function test_verify_payment_with_paystack_success(): void
    {
        Config::set('payment.default_provider', 'paystack');
        $paymentService = new PaymentService;

        Http::fake([
            'api.paystack.co/transaction/verify/ref_test123' => Http::response([
                'status' => true,
                'data' => [
                    'status' => 'success',
                    'amount' => 10000, // 100.00 in kobo
                    'reference' => 'ref_test123',
                    'customer' => [
                        'email' => 'test@example.com',
                    ],
                    'metadata' => ['user_id' => $this->user->id],
                ],
            ], 200),
        ]);

        $result = $paymentService->verifyPayment('ref_test123');

        $this->assertEquals('success', $result['status']);
        $this->assertEquals(100.00, $result['amount']);
        $this->assertEquals('ref_test123', $result['reference']);
        $this->assertEquals('test@example.com', $result['customer_email']);
    }

    public function test_verify_payment_with_flutterwave_success(): void
    {
        Config::set('payment.default_provider', 'flutterwave');
        $paymentService = new PaymentService;

        Http::fake([
            'api.flutterwave.com/v3/transactions/verify_by_reference*' => Http::response([
                'status' => 'success',
                'data' => [
                    'status' => 'successful',
                    'amount' => 100.00,
                    'tx_ref' => 'ref_test123',
                    'customer' => [
                        'email' => 'test@example.com',
                    ],
                    'meta' => ['user_id' => $this->user->id],
                ],
            ], 200),
        ]);

        $result = $paymentService->verifyPayment('ref_test123');

        $this->assertEquals('success', $result['status']);
        $this->assertEquals(100.00, $result['amount']);
        $this->assertEquals('ref_test123', $result['reference']);
        $this->assertEquals('test@example.com', $result['customer_email']);
    }

    public function test_verify_payment_with_mock(): void
    {
        Config::set('payment.default_provider', 'mock');
        $paymentService = new PaymentService;

        $result = $paymentService->verifyPayment('ref_test123');

        $this->assertEquals('success', $result['status']);
        $this->assertEquals(100.00, $result['amount']);
        $this->assertEquals('ref_test123', $result['reference']);
        $this->assertEquals('test@example.com', $result['customer_email']);
        $this->assertTrue($result['metadata']['mock']);
    }

    public function test_verify_payment_handles_failure(): void
    {
        Config::set('payment.default_provider', 'paystack');
        $paymentService = new PaymentService;

        Http::fake([
            'api.paystack.co/transaction/verify/ref_test123' => Http::response([
                'status' => true,
                'data' => [
                    'status' => 'failed',
                    'reference' => 'ref_test123',
                ],
            ], 200),
        ]);

        $result = $paymentService->verifyPayment('ref_test123');

        $this->assertEquals('failed', $result['status']);
        $this->assertEquals('Payment verification failed', $result['message']);
    }

    public function test_verify_payment_handles_exception(): void
    {
        Config::set('payment.default_provider', 'paystack');
        $paymentService = new PaymentService;

        Http::fake([
            'api.paystack.co/transaction/verify/ref_test123' => Http::response([], 500),
        ]);

        $result = $paymentService->verifyPayment('ref_test123');

        $this->assertEquals('failed', $result['status']);
        $this->assertArrayHasKey('message', $result);
    }

    public function test_process_cashback_handles_http_exception(): void
    {
        Config::set('payment.default_provider', 'paystack');
        $paymentService = new PaymentService;

        Http::fake([
            'api.paystack.co/transferrecipient' => Http::response([], 500),
        ]);

        $result = $paymentService->processCashback($this->user, 100.00);

        $this->assertEquals('failed', $result['status']);
        $this->assertArrayHasKey('error', $result);
    }

    public function test_initialize_payment_handles_failure(): void
    {
        Config::set('payment.default_provider', 'paystack');
        $paymentService = new PaymentService;

        Http::fake([
            'api.paystack.co/transaction/initialize' => Http::response([
                'status' => false,
                'message' => 'Invalid amount',
            ], 400),
        ]);

        $result = $paymentService->initializePayment($this->user, 100.00, 'ref_test123');

        $this->assertEquals('error', $result['status']);
        $this->assertArrayHasKey('message', $result);
    }

    public function test_mock_cashback_simulates_different_scenarios(): void
    {
        Config::set('payment.default_provider', 'mock');
        $paymentService = new PaymentService;

        $results = [];
        for ($i = 0; $i < 100; $i++) {
            $result = $paymentService->processCashback($this->user, 100.00);
            $results[] = $result['status'];
        }

        // Should have a mix of statuses (though exact distribution may vary)
        $uniqueStatuses = array_unique($results);
        $this->assertGreaterThan(1, count($uniqueStatuses));
    }

    public function test_payment_service_constructor_loads_config(): void
    {
        Config::set('payment.default_provider', 'custom_provider');
        Config::set('payment.providers.custom_provider.secret_key', 'custom_secret');
        Config::set('payment.providers.custom_provider.public_key', 'custom_public');
        Config::set('payment.providers.custom_provider.base_url', 'https://custom.api.com');

        $paymentService = new PaymentService;

        // The service should use the custom provider for mock processing
        $result = $paymentService->processCashback($this->user, 100.00);

        $this->assertArrayHasKey('status', $result);
    }
}
