<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\Payment\Providers\MockPaymentProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MockPaymentProviderTest extends TestCase
{
    use RefreshDatabase;

    protected MockPaymentProvider $mockProvider;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockProvider = new MockPaymentProvider([
            'enabled' => true,
            'should_fail' => false,
            'failure_rate' => 0.0,
        ]);
    }

    public function test_can_initialize_payment(): void
    {
        $user = User::factory()->create();
        $amount = 100.00;
        $reference = 'test_ref_123';

        $result = $this->mockProvider->initializePayment($user, $amount, $reference);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('authorization_url', $result['data']);
        $this->assertArrayHasKey('access_code', $result['data']);
        $this->assertArrayHasKey('reference', $result['data']);
        $this->assertEquals($reference, $result['data']['reference']);
    }

    public function test_can_verify_payment(): void
    {
        $reference = 'test_ref_123';

        $result = $this->mockProvider->verifyPayment($reference);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('transaction_id', $result['data']);
        $this->assertArrayHasKey('status', $result['data']);
        $this->assertArrayHasKey('amount', $result['data']);
        $this->assertArrayHasKey('currency', $result['data']);
        $this->assertEquals('success', $result['data']['status']);
    }

    public function test_can_process_cashback(): void
    {
        $user = User::factory()->create();
        $amount = 50.00;

        $result = $this->mockProvider->processCashback($user, $amount);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('transaction_id', $result['data']);
        $this->assertArrayHasKey('reference', $result['data']);
        $this->assertArrayHasKey('status', $result['data']);
        $this->assertArrayHasKey('amount', $result['data']);
        $this->assertEquals('success', $result['data']['status']);
        $this->assertEquals($amount, $result['data']['amount']);
    }

    public function test_can_simulate_failures(): void
    {
        $provider = new MockPaymentProvider([
            'enabled' => true,
            'should_fail' => true,
            'failure_rate' => 1.0, // 100% failure rate
        ]);

        $user = User::factory()->create();
        $amount = 100.00;
        $reference = 'test_ref_123';

        $result = $provider->initializePayment($user, $amount, $reference);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
        $this->assertNotEmpty($result['error']);
    }

    public function test_can_configure_mock_behavior(): void
    {
        $provider = new MockPaymentProvider([
            'enabled' => true,
            'should_fail' => false,
            'failure_rate' => 0.0,
        ]);

        // Configure to fail
        $provider->configureMockBehavior([
            'should_fail' => true,
            'failure_rate' => 0.5,
        ]);

        $config = $provider->getMockConfiguration();
        $this->assertTrue($config['should_fail']);
        $this->assertEquals(0.5, $config['failure_rate']);
    }

    public function test_can_reset_mock_behavior(): void
    {
        $provider = new MockPaymentProvider([
            'enabled' => true,
            'should_fail' => true,
            'failure_rate' => 1.0,
        ]);

        $provider->resetMockBehavior();

        $config = $provider->getMockConfiguration();
        $this->assertFalse($config['should_fail']);
        $this->assertEquals(0.0, $config['failure_rate']);
    }

    public function test_validates_payment_data(): void
    {
        $user = User::factory()->create();
        $amount = -100.00; // Invalid negative amount
        $reference = 'test_ref_123';

        $result = $this->mockProvider->initializePayment($user, $amount, $reference);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    public function test_returns_supported_currencies(): void
    {
        $currencies = $this->mockProvider->getSupportedCurrencies();

        $this->assertContains('NGN', $currencies);
        $this->assertContains('USD', $currencies);
        $this->assertContains('GBP', $currencies);
    }

    public function test_returns_amount_limits(): void
    {
        $minAmount = $this->mockProvider->getMinimumAmount();
        $maxAmount = $this->mockProvider->getMaximumAmount();

        $this->assertGreaterThan(0, $minAmount);
        $this->assertGreaterThan($minAmount, $maxAmount);
    }

    public function test_is_available(): void
    {
        $this->assertTrue($this->mockProvider->isAvailable());
    }

    public function test_handles_exceptions_gracefully(): void
    {
        $user = User::factory()->create();
        $amount = 100.00;
        $reference = 'test_ref_123';

        // Create a provider that will throw an exception
        $provider = new MockPaymentProvider([
            'enabled' => true,
            'should_fail' => true,
            'failure_rate' => 1.0,
        ]);

        $result = $provider->initializePayment($user, $amount, $reference);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
        $this->assertNotEmpty($result['error']);
    }
}
