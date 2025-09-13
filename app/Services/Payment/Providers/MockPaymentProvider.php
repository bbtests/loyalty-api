<?php

namespace App\Services\Payment\Providers;

use App\Models\User;

class MockPaymentProvider extends BasePaymentProvider
{
    /** @var array<string, mixed> */
    private array $mockResponses = [];

    private bool $shouldFail = false;

    private float $failureRate = 0.0;

    public function __construct(array $config)
    {
        parent::__construct($config, 'mock');

        // Configure mock behavior
        $this->shouldFail = $config['should_fail'] ?? false;
        $this->failureRate = $config['failure_rate'] ?? 0.0;
        $this->mockResponses = $config['mock_responses'] ?? [];
    }

    public function initializePayment(User $user, float $amount, string $reference, ?string $callbackUrl = null): array
    {
        $validationError = $this->validatePaymentData($user, $amount);
        if ($validationError) {
            return [
                'success' => false,
                'error' => $validationError,
            ];
        }

        // Simulate random failures based on failure rate
        if ($this->shouldFail || (mt_rand() / mt_getrandmax()) < $this->failureRate) {
            return $this->simulateFailure('initialize_payment', [
                'user_id' => $user->id,
                'amount' => $amount,
                'reference' => $reference,
            ]);
        }

        try {
            // Simulate API delay
            usleep(mt_rand(100000, 500000)); // 100-500ms delay

            $responseData = [
                'authorization_url' => "https://mock-payment.com/pay/{$reference}",
                'access_code' => 'mock_access_'.$reference,
                'reference' => $reference,
                'transaction_id' => 'mock_txn_'.time(),
            ];

            $this->logPayment('initialize_payment', [
                'user_id' => $user->id,
                'amount' => $amount,
                'reference' => $reference,
                'provider' => 'mock',
            ]);

            return [
                'success' => true,
                'data' => $responseData,
            ];

        } catch (\Exception $e) {
            $error = 'Mock payment initialization exception: '.$e->getMessage();
            $this->logPayment('initialize_payment', [
                'user_id' => $user->id,
                'amount' => $amount,
                'reference' => $reference,
            ], $error);

            return [
                'success' => false,
                'error' => $error,
            ];
        }
    }

    public function verifyPayment(string $reference): array
    {
        try {
            // Simulate API delay
            usleep(mt_rand(50000, 200000)); // 50-200ms delay

            // Simulate random failures
            if ($this->shouldFail || (mt_rand() / mt_getrandmax()) < $this->failureRate) {
                return $this->simulateFailure('verify_payment', [
                    'reference' => $reference,
                ]);
            }

            // Mock successful verification
            $responseData = [
                'transaction_id' => 'mock_txn_'.substr($reference, -8),
                'status' => 'success',
                'amount' => mt_rand(100, 10000) / 100, // Random amount between 1-100
                'currency' => 'NGN',
                'reference' => $reference,
                'customer' => [
                    'email' => 'customer@example.com',
                    'name' => 'Mock Customer',
                ],
                'metadata' => [
                    'mock_provider' => true,
                    'verified_at' => now()->toISOString(),
                ],
            ];

            $this->logPayment('verify_payment', [
                'reference' => $reference,
                'provider' => 'mock',
            ]);

            return [
                'success' => true,
                'data' => $responseData,
            ];

        } catch (\Exception $e) {
            $error = 'Mock payment verification exception: '.$e->getMessage();
            $this->logPayment('verify_payment', [
                'reference' => $reference,
            ], $error);

            return [
                'success' => false,
                'error' => $error,
            ];
        }
    }

    public function processCashback(User $user, float $amount): array
    {
        $validationError = $this->validatePaymentData($user, $amount);
        if ($validationError) {
            return [
                'success' => false,
                'error' => $validationError,
            ];
        }

        try {
            // Simulate API delay
            usleep(mt_rand(200000, 800000)); // 200-800ms delay

            // Simulate random failures
            if ($this->shouldFail || (mt_rand() / mt_getrandmax()) < $this->failureRate) {
                return $this->simulateFailure('process_cashback', [
                    'user_id' => $user->id,
                    'amount' => $amount,
                ]);
            }

            // Mock successful cashback processing
            $responseData = [
                'transaction_id' => 'mock_cashback_'.time(),
                'reference' => $this->generateReference('cashback'),
                'status' => 'success',
                'amount' => $amount,
                'recipient_code' => 'mock_recipient_'.$user->id,
                'processing_time' => mt_rand(1000, 5000).'ms',
                'mock_provider' => true,
            ];

            $this->logPayment('process_cashback', [
                'user_id' => $user->id,
                'amount' => $amount,
                'provider' => 'mock',
            ]);

            return [
                'success' => true,
                'data' => $responseData,
            ];

        } catch (\Exception $e) {
            $error = 'Mock cashback processing exception: '.$e->getMessage();
            $this->logPayment('process_cashback', [
                'user_id' => $user->id,
                'amount' => $amount,
            ], $error);

            return [
                'success' => false,
                'error' => $error,
            ];
        }
    }

    public function isAvailable(): bool
    {
        return parent::isAvailable();
    }

    public function getSupportedCurrencies(): array
    {
        return ['NGN', 'USD', 'GBP', 'EUR', 'ZAR', 'KES', 'GHS'];
    }

    public function getMinimumAmount(): float
    {
        return 0.01; // Very low minimum for testing
    }

    public function getMaximumAmount(): float
    {
        return 1000000.0; // High maximum for testing
    }

    /**
     * Simulate various failure scenarios
     *
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function simulateFailure(string $operation, array $context): array
    {
        $failureTypes = [
            'network_timeout',
            'insufficient_funds',
            'invalid_account',
            'service_unavailable',
            'rate_limit_exceeded',
        ];

        $failureType = $failureTypes[array_rand($failureTypes)];
        $errorMessage = $this->getFailureMessage($failureType);

        $this->logPayment($operation, $context, $errorMessage);

        return [
            'success' => false,
            'error' => $errorMessage,
        ];
    }

    /**
     * Get appropriate error message for failure type
     */
    private function getFailureMessage(string $failureType): string
    {
        $messages = [
            'network_timeout' => 'Payment service timeout. Please try again.',
            'insufficient_funds' => 'Insufficient funds in merchant account.',
            'invalid_account' => 'Invalid recipient account details.',
            'service_unavailable' => 'Payment service temporarily unavailable.',
            'rate_limit_exceeded' => 'Too many requests. Please try again later.',
        ];

        return $messages[$failureType] ?? 'Unknown payment error occurred.';
    }

    /**
     * Configure mock behavior for testing
     *
     * @param  array<string, mixed>  $config
     */
    public function configureMockBehavior(array $config): void
    {
        $this->shouldFail = $config['should_fail'] ?? $this->shouldFail;
        $this->failureRate = $config['failure_rate'] ?? $this->failureRate;
        $this->mockResponses = array_merge($this->mockResponses, $config['mock_responses'] ?? []);
    }

    /**
     * Reset mock behavior to defaults
     */
    public function resetMockBehavior(): void
    {
        $this->shouldFail = false;
        $this->failureRate = 0.0;
        $this->mockResponses = [];
    }

    /**
     * Get current mock configuration
     *
     * @return array<string, mixed>
     */
    public function getMockConfiguration(): array
    {
        return [
            'should_fail' => $this->shouldFail,
            'failure_rate' => $this->failureRate,
            'mock_responses' => $this->mockResponses,
        ];
    }
}
