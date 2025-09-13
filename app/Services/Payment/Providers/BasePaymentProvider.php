<?php

namespace App\Services\Payment\Providers;

use App\Contracts\PaymentProviderInterface;
use App\Models\User;
use Illuminate\Support\Facades\Log;

abstract class BasePaymentProvider implements PaymentProviderInterface
{
    /** @var array<string, mixed> */
    protected array $config;

    protected string $providerName;

    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(array $config, string $providerName)
    {
        $this->config = $config;
        $this->providerName = $providerName;
    }

    /**
     * @return array<string, mixed>
     */
    abstract public function initializePayment(User $user, float $amount, string $reference, ?string $callbackUrl = null): array;

    /**
     * @return array<string, mixed>
     */
    abstract public function verifyPayment(string $reference): array;

    /**
     * @return array<string, mixed>
     */
    abstract public function processCashback(User $user, float $amount): array;

    /**
     * @return array<string, mixed>
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    public function isAvailable(): bool
    {
        return $this->config['enabled'] ?? false;
    }

    public function getName(): string
    {
        return $this->providerName;
    }

    /**
     * @return array<string>
     */
    public function getSupportedCurrencies(): array
    {
        return ['NGN', 'USD', 'GBP', 'EUR'];
    }

    public function getMinimumAmount(): float
    {
        return 1.0;
    }

    public function getMaximumAmount(): float
    {
        return 1000000.0;
    }

    /**
     * Validate payment data
     */
    protected function validatePaymentData(User $user, float $amount): ?string
    {
        if ($amount <= 0) {
            return 'Amount must be greater than 0';
        }

        if ($amount < $this->getMinimumAmount()) {
            return "Amount must be at least {$this->getMinimumAmount()}";
        }

        if ($amount > $this->getMaximumAmount()) {
            return "Amount must not exceed {$this->getMaximumAmount()}";
        }

        if (empty($user->email)) {
            return 'User email is required for payment';
        }

        return null;
    }

    /**
     * Generate a unique reference
     */
    protected function generateReference(string $prefix = 'pay'): string
    {
        $timestamp = time();
        $random = substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyz'), 0, 8);

        return "{$prefix}_{$timestamp}_{$random}";
    }

    /**
     * Log payment activity
     *
     * @param  array<string, mixed>  $data
     */
    protected function logPayment(string $action, array $data, ?string $error = null): void
    {
        $logData = [
            'provider' => $this->providerName,
            'action' => $action,
            'data' => $data,
        ];

        if ($error) {
            $logData['error'] = $error;
            Log::error('Payment Error', $logData);
        } else {
            Log::info('Payment Activity', $logData);
        }
    }

    /**
     * Create standardized response
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function createResponse(bool $success, array $data = [], ?string $error = null): array
    {
        $response = [
            'status' => $success ? 'success' : 'error',
            'provider' => $this->providerName,
        ];

        if ($success) {
            $response = array_merge($response, $data);
        } else {
            $response['error'] = $error ?? 'Payment processing failed';
        }

        return $response;
    }
}
