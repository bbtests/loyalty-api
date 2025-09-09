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
        Config::set('payment.providers.paystack.enabled', true);
        Config::set('payment.providers.paystack.secret_key', 'sk_test_secret');
        Config::set('payment.providers.paystack.public_key', 'pk_test_public');
        Config::set('payment.providers.paystack.base_url', 'https://api.paystack.co');
        Config::set('payment.providers.flutterwave.enabled', true);
        Config::set('payment.providers.flutterwave.secret_key', 'flw_test_secret');
        Config::set('payment.providers.flutterwave.public_key', 'flw_test_public');
        Config::set('payment.providers.flutterwave.base_url', 'https://api.flutterwave.com/v3');
        Config::set('app.url', 'http://localhost');

        $this->paymentService = new PaymentService;
        $this->user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
    }

    public function test_payment_service_uses_paystack_as_default(): void
    {
        $this->assertEquals('paystack', $this->paymentService->getProvider()->getName());
    }

    public function test_get_public_key_returns_correct_key(): void
    {
        $publicKey = $this->paymentService->getPublicKey();

        $this->assertEquals('pk_test_public', $publicKey);
    }

    public function test_get_public_key_with_specific_provider(): void
    {
        $publicKey = $this->paymentService->getPublicKey('flutterwave');

        $this->assertEquals('flw_test_public', $publicKey);
    }

    public function test_get_available_providers(): void
    {
        $providers = $this->paymentService->getAvailableProviders();

        $this->assertArrayHasKey('paystack', $providers);
        $this->assertArrayHasKey('flutterwave', $providers);
        $this->assertCount(2, $providers);
    }

    public function test_is_provider_available(): void
    {
        $this->assertTrue($this->paymentService->isProviderAvailable('paystack'));
        $this->assertTrue($this->paymentService->isProviderAvailable('flutterwave'));
        $this->assertFalse($this->paymentService->isProviderAvailable('nonexistent'));
    }

    public function test_get_provider_info(): void
    {
        $info = $this->paymentService->getProviderInfo('paystack');

        $this->assertEquals('paystack', $info['name']);
        $this->assertTrue($info['available']);
        $this->assertArrayHasKey('supported_currencies', $info);
        $this->assertArrayHasKey('minimum_amount', $info);
        $this->assertArrayHasKey('maximum_amount', $info);
    }

    public function test_get_all_providers_info(): void
    {
        $allInfo = $this->paymentService->getAllProvidersInfo();

        $this->assertArrayHasKey('paystack', $allInfo);
        $this->assertArrayHasKey('flutterwave', $allInfo);
        $this->assertCount(2, $allInfo);
    }

    public function test_get_configuration(): void
    {
        $config = $this->paymentService->getConfiguration();

        $this->assertArrayHasKey('providers', $config);
        $this->assertArrayHasKey('supported_currencies', $config);
        $this->assertArrayHasKey('amount_limits', $config);
        $this->assertArrayHasKey('default_provider', $config);
        $this->assertEquals('paystack', $config['default_provider']);
    }

    public function test_initialize_payment_with_paystack(): void
    {
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

        $result = $this->paymentService->initializePayment($this->user, 100.00, 'ref_test123');

        $this->assertEquals('success', $result['status']);
        $this->assertEquals('https://checkout.paystack.com/test123', $result['authorization_url']);
        $this->assertEquals('access_test123', $result['access_code']);
        $this->assertEquals('ref_test123', $result['reference']);
    }

    public function test_initialize_payment_with_flutterwave(): void
    {
        Http::fake([
            'api.flutterwave.com/v3/payments' => Http::response([
                'status' => 'success',
                'data' => [
                    'link' => 'https://checkout.flutterwave.com/test123',
                    'tx_ref' => 'ref_test123',
                    'id' => 'fw_test123',
                ],
            ], 200),
        ]);

        $result = $this->paymentService->initializePayment($this->user, 100.00, 'ref_test123', 'flutterwave');

        $this->assertEquals('success', $result['status']);
        $this->assertEquals('https://checkout.flutterwave.com/test123', $result['authorization_url']);
        $this->assertEquals('ref_test123', $result['reference']);
    }

    public function test_verify_payment_with_paystack(): void
    {
        Http::fake([
            'api.paystack.co/transaction/verify/ref_test123' => Http::response([
                'status' => true,
                'data' => [
                    'id' => 12345,
                    'status' => 'success',
                    'amount' => 10000, // 100.00 in kobo
                    'currency' => 'NGN',
                    'reference' => 'ref_test123',
                    'customer' => [
                        'email' => 'test@example.com',
                    ],
                    'metadata' => ['user_id' => $this->user->id],
                ],
            ], 200),
        ]);

        $result = $this->paymentService->verifyPayment('ref_test123');

        $this->assertEquals('success', $result['status']);
        $this->assertEquals(100.00, $result['amount']);
        $this->assertEquals('ref_test123', $result['reference']);
        $this->assertEquals('test@example.com', $result['customer']['email']);
    }

    public function test_verify_payment_with_flutterwave(): void
    {
        Http::fake([
            'api.flutterwave.com/v3/transactions/ref_test123/verify' => Http::response([
                'status' => 'success',
                'data' => [
                    'id' => 12345,
                    'status' => 'successful',
                    'amount' => 100.00,
                    'currency' => 'NGN',
                    'tx_ref' => 'ref_test123',
                    'customer' => [
                        'email' => 'test@example.com',
                    ],
                    'meta' => ['user_id' => $this->user->id],
                ],
            ], 200),
        ]);

        $result = $this->paymentService->verifyPayment('ref_test123', 'flutterwave');

        $this->assertEquals('successful', $result['status']);
        $this->assertEquals(100.00, $result['amount']);
        $this->assertEquals('ref_test123', $result['reference']);
        $this->assertEquals('test@example.com', $result['customer']['email']);
    }

    public function test_process_cashback_with_paystack(): void
    {
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

        $result = $this->paymentService->processCashback($this->user, 100.00);

        $this->assertEquals('success', $result['status']);
        $this->assertEquals('TRF_test123', $result['transaction_id']);
        $this->assertEquals('cashback_test123', $result['reference']);
    }

    public function test_process_cashback_with_flutterwave(): void
    {
        Http::fake([
            'api.flutterwave.com/v3/beneficiaries' => Http::response([
                'status' => 'success',
                'data' => [
                    'id' => 12345,
                ],
            ], 200),
            'api.flutterwave.com/v3/transfers' => Http::response([
                'status' => 'success',
                'data' => [
                    'id' => 12345,
                    'reference' => 'fw_cashback_test123',
                ],
            ], 200),
        ]);

        $result = $this->paymentService->processCashback($this->user, 100.00, 'flutterwave');

        $this->assertEquals('success', $result['status']);
        $this->assertEquals(12345, $result['transaction_id']);
        $this->assertEquals('fw_cashback_test123', $result['reference']);
    }

    public function test_process_cashback_uses_cached_recipient(): void
    {
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

        $result = $this->paymentService->processCashback($this->user, 100.00);

        $this->assertEquals('success', $result['status']);

        // Should not call transferrecipient endpoint since we have cached recipient
        Http::assertNotSent(function ($request) {
            return str_contains($request->url(), 'transferrecipient');
        });
    }

    public function test_process_payment_with_fallback(): void
    {
        // Mock paystack to fail
        Http::fake([
            'api.paystack.co/transaction/initialize' => Http::response([
                'status' => false,
                'message' => 'Service unavailable',
            ], 503),
            'api.flutterwave.com/v3/payments' => Http::response([
                'status' => 'success',
                'data' => [
                    'link' => 'https://checkout.flutterwave.com/test123',
                    'tx_ref' => 'ref_test123',
                    'id' => 'fw_test123',
                ],
            ], 200),
        ]);

        $result = $this->paymentService->processPaymentWithFallback(
            $this->user,
            100.00,
            'ref_test123',
            ['paystack', 'flutterwave']
        );

        $this->assertEquals('success', $result['status']);
        $this->assertEquals('https://checkout.flutterwave.com/test123', $result['authorization_url']);
    }

    public function test_process_cashback_with_fallback(): void
    {
        // Mock paystack to fail
        Http::fake([
            'api.paystack.co/transferrecipient' => Http::response([
                'status' => false,
                'message' => 'Service unavailable',
            ], 503),
            'api.flutterwave.com/v3/beneficiaries' => Http::response([
                'status' => 'success',
                'data' => [
                    'id' => 12345,
                ],
            ], 200),
            'api.flutterwave.com/v3/transfers' => Http::response([
                'status' => 'success',
                'data' => [
                    'id' => 12345,
                    'reference' => 'fw_cashback_test123',
                ],
            ], 200),
        ]);

        $result = $this->paymentService->processCashbackWithFallback(
            $this->user,
            100.00,
            ['paystack', 'flutterwave']
        );

        $this->assertEquals('success', $result['status']);
        $this->assertEquals(12345, $result['transaction_id']);
    }

    public function test_fallback_returns_error_when_all_providers_fail(): void
    {
        Http::fake([
            'api.paystack.co/transaction/initialize' => Http::response([
                'status' => false,
                'message' => 'Service unavailable',
            ], 503),
            'api.flutterwave.com/v3/payments' => Http::response([
                'status' => 'error',
                'message' => 'Service unavailable',
            ], 503),
        ]);

        $result = $this->paymentService->processPaymentWithFallback(
            $this->user,
            100.00,
            'ref_test123'
        );

        $this->assertEquals('error', $result['status']);
        $this->assertEquals('All payment providers failed', $result['error']);
    }

    public function test_register_and_unregister_provider(): void
    {
        // Set up configuration for the custom provider
        Config::set('payment.providers.custom.enabled', true);
        Config::set('payment.providers.custom.secret_key', 'sk_test_secret');
        Config::set('payment.providers.custom.public_key', 'pk_test_public');
        Config::set('payment.providers.custom.base_url', 'https://api.paystack.co');

        // Create a new PaymentService instance with the updated configuration
        $paymentService = new PaymentService;

        // Register a custom provider (using existing PaystackProvider as example)
        $paymentService->registerProvider('custom', 'App\\Services\\Payment\\Providers\\PaystackProvider');

        $this->assertTrue($paymentService->isProviderAvailable('custom'));

        // Unregister the provider
        $paymentService->unregisterProvider('custom');

        $this->assertFalse($paymentService->isProviderAvailable('custom'));
    }

    public function test_get_supported_currencies(): void
    {
        $currencies = $this->paymentService->getSupportedCurrencies();

        $this->assertContains('NGN', $currencies);
        $this->assertContains('USD', $currencies);
    }

    public function test_get_minimum_and_maximum_amounts(): void
    {
        $minAmount = $this->paymentService->getMinimumAmount();
        $maxAmount = $this->paymentService->getMaximumAmount();

        $this->assertGreaterThan(0, $minAmount);
        $this->assertGreaterThan($minAmount, $maxAmount);
    }

    public function test_handles_invalid_provider(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Payment provider 'invalid' not found");

        $this->paymentService->getProvider('invalid');
    }

    public function test_handles_unavailable_provider(): void
    {
        // Create a provider that will be unavailable by disabling it
        Config::set('payment.providers.unavailable.enabled', false);
        Config::set('payment.providers.unavailable.secret_key', '');
        Config::set('payment.providers.unavailable.public_key', '');
        Config::set('payment.providers.unavailable.base_url', '');

        $this->paymentService->registerProvider('unavailable', 'App\\Services\\Payment\\Providers\\PaystackProvider');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Payment provider 'unavailable' is not available");

        $this->paymentService->getProvider('unavailable');
    }

    public function test_initialize_payment_handles_failure(): void
    {
        Http::fake([
            'api.paystack.co/transaction/initialize' => Http::response([
                'status' => false,
                'message' => 'Invalid amount',
            ], 400),
        ]);

        $result = $this->paymentService->initializePayment($this->user, 100.00, 'ref_test123');

        $this->assertEquals('error', $result['status']);
        $this->assertStringContainsString('Payment initialization failed', $result['error']);
    }

    public function test_verify_payment_handles_failure(): void
    {
        Http::fake([
            'api.paystack.co/transaction/verify/ref_test123' => Http::response([
                'status' => true,
                'data' => [
                    'id' => 12345,
                    'status' => 'failed',
                    'amount' => 10000,
                    'currency' => 'NGN',
                    'reference' => 'ref_test123',
                    'customer' => [
                        'email' => 'test@example.com',
                    ],
                ],
            ], 200),
        ]);

        $result = $this->paymentService->verifyPayment('ref_test123');

        $this->assertEquals('failed', $result['status']);
        $this->assertEquals('ref_test123', $result['reference']);
    }

    public function test_process_cashback_handles_failure(): void
    {
        Http::fake([
            'api.paystack.co/transferrecipient' => Http::response([
                'status' => false,
                'message' => 'Invalid account details',
            ], 400),
        ]);

        $result = $this->paymentService->processCashback($this->user, 100.00);

        $this->assertEquals('error', $result['status']);
        $this->assertArrayHasKey('error', $result);
    }

    public function test_handles_http_exceptions(): void
    {
        Http::fake([
            'api.paystack.co/transaction/verify/ref_test123' => Http::response([], 500),
        ]);

        $result = $this->paymentService->verifyPayment('ref_test123');

        $this->assertEquals('error', $result['status']);
        $this->assertStringContainsString('Payment verification failed', $result['error']);
    }

    public function test_payment_service_constructor_loads_config(): void
    {
        Config::set('payment.default_provider', 'paystack');
        Config::set('payment.providers.paystack.secret_key', 'sk_test_secret');
        Config::set('payment.providers.paystack.public_key', 'pk_test_public');
        Config::set('payment.providers.paystack.base_url', 'https://api.paystack.co');

        $paymentService = new PaymentService;

        $this->assertInstanceOf(PaymentService::class, $paymentService);
        $this->assertTrue($paymentService->isProviderAvailable('paystack'));
    }
}
