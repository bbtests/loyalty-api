<?php

namespace App\Services;

use App\Contracts\PaymentProviderInterface;
use App\Models\User;
use App\Services\Payment\Providers\FlutterwaveProvider;
use App\Services\Payment\Providers\MockPaymentProvider;
use App\Services\Payment\Providers\PaystackProvider;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    private string $defaultProvider;

    /** @var array<string, mixed> */
    private array $config;

    /** @var array<string, string> */
    private array $providers = [];

    public function __construct()
    {
        $this->config = Config::get('payment', []);
        $this->defaultProvider = $this->config['default_provider'] ?? 'paystack';
        $this->initializeProviders();
    }

    /**
     * Initialize all available payment providers
     */
    private function initializeProviders(): void
    {
        $this->providers = [
            'paystack' => PaystackProvider::class,
            'flutterwave' => FlutterwaveProvider::class,
            'mock' => MockPaymentProvider::class,
        ];

        Log::info('Payment providers initialized', [
            'providers' => array_keys($this->providers),
            'default' => $this->defaultProvider,
        ]);
    }

    /**
     * Create a payment provider instance
     */
    private function createProvider(string $providerName): PaymentProviderInterface
    {
        if (! isset($this->providers[$providerName])) {
            throw new \InvalidArgumentException("Payment provider '{$providerName}' not found");
        }

        $providerClass = $this->providers[$providerName];
        $config = $this->config['providers'][$providerName] ?? [];

        return new $providerClass($config);
    }

    /**
     * Get a payment provider instance
     */
    public function getProvider(?string $provider = null): PaymentProviderInterface
    {
        $provider = $provider ?? $this->defaultProvider;

        if (! isset($this->providers[$provider])) {
            throw new \InvalidArgumentException("Payment provider '{$provider}' not found");
        }

        $providerInstance = $this->createProvider($provider);

        if (! $providerInstance->isAvailable()) {
            throw new \RuntimeException("Payment provider '{$provider}' is not available");
        }

        return $providerInstance;
    }

    /**
     * Get all available providers
     *
     * @return array<string, PaymentProviderInterface>
     */
    public function getAvailableProviders(): array
    {
        $availableProviders = [];

        foreach ($this->providers as $name => $providerClass) {
            try {
                $provider = $this->createProvider($name);
                if ($provider->isAvailable()) {
                    $availableProviders[$name] = $provider;
                }
            } catch (\Exception $e) {
                Log::warning("Failed to create provider {$name}", [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $availableProviders;
    }

    /**
     * Initialize payment transaction
     *
     * @return array<string, mixed>
     */
    public function initializePayment(User $user, float $amount, string $reference, ?string $provider = null, ?string $callbackUrl = null): array
    {
        try {
            $providerInstance = $this->getProvider($provider);

            Log::info('Initializing payment', [
                'provider' => $providerInstance->getName(),
                'user_id' => $user->id,
                'amount' => $amount,
                'reference' => $reference,
            ]);

            return $providerInstance->initializePayment($user, $amount, $reference, $callbackUrl);
        } catch (\Exception $e) {
            Log::error('Payment initialization failed', [
                'user_id' => $user->id,
                'amount' => $amount,
                'reference' => $reference,
                'provider' => $provider,
                'error' => $e->getMessage(),
            ]);

            return [
                'status' => 'error',
                'error' => 'Payment initialization failed: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Verify payment transaction
     *
     * @return array<string, mixed>
     */
    public function verifyPayment(string $reference, ?string $provider = null): array
    {
        try {
            $providerInstance = $this->getProvider($provider);

            Log::info('Verifying payment', [
                'provider' => $providerInstance->getName(),
                'reference' => $reference,
            ]);

            return $providerInstance->verifyPayment($reference);
        } catch (\Exception $e) {
            Log::error('Payment verification failed', [
                'reference' => $reference,
                'provider' => $provider,
                'error' => $e->getMessage(),
            ]);

            return [
                'status' => 'error',
                'error' => 'Payment verification failed: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Process cashback payment
     *
     * @return array<string, mixed>
     */
    public function processCashback(User $user, float $amount, ?string $provider = null): array
    {
        try {
            $providerInstance = $this->getProvider($provider);

            Log::info('Processing cashback', [
                'provider' => $providerInstance->getName(),
                'user_id' => $user->id,
                'amount' => $amount,
            ]);

            return $providerInstance->processCashback($user, $amount);
        } catch (\Exception $e) {
            Log::error('Cashback processing failed', [
                'user_id' => $user->id,
                'amount' => $amount,
                'provider' => $provider,
                'error' => $e->getMessage(),
            ]);

            return [
                'status' => 'error',
                'error' => 'Cashback processing failed: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Get public key for provider
     */
    public function getPublicKey(?string $provider = null): string
    {
        $providerInstance = $this->getProvider($provider);
        $config = $providerInstance->getConfig();

        return $config['public_key'] ?? '';
    }

    /**
     * Get supported currencies across all providers
     *
     * @return array<string>
     */
    public function getSupportedCurrencies(): array
    {
        $currencies = [];

        foreach ($this->getAvailableProviders() as $provider) {
            $currencies = array_merge($currencies, $provider->getSupportedCurrencies());
        }

        return array_unique($currencies);
    }

    /**
     * Get minimum amount across all providers
     */
    public function getMinimumAmount(): float
    {
        $amounts = [];

        foreach ($this->getAvailableProviders() as $provider) {
            $amounts[] = $provider->getMinimumAmount();
        }

        return empty($amounts) ? 1.0 : min($amounts);
    }

    /**
     * Get maximum amount across all providers
     */
    public function getMaximumAmount(): float
    {
        $amounts = [];

        foreach ($this->getAvailableProviders() as $provider) {
            $amounts[] = $provider->getMaximumAmount();
        }

        return empty($amounts) ? 1000000.0 : max($amounts);
    }

    /**
     * Check if a provider is available
     */
    public function isProviderAvailable(string $provider): bool
    {
        if (! isset($this->providers[$provider])) {
            return false;
        }

        try {
            $providerInstance = $this->createProvider($provider);

            return $providerInstance->isAvailable();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get provider information
     *
     * @return array<string, mixed>
     */
    public function getProviderInfo(?string $provider = null): array
    {
        $providerInstance = $this->getProvider($provider);

        return [
            'name' => $providerInstance->getName(),
            'available' => $providerInstance->isAvailable(),
            'supported_currencies' => $providerInstance->getSupportedCurrencies(),
            'minimum_amount' => $providerInstance->getMinimumAmount(),
            'maximum_amount' => $providerInstance->getMaximumAmount(),
        ];
    }

    /**
     * Get all providers information
     *
     * @return array<string, array<string, mixed>>
     */
    public function getAllProvidersInfo(): array
    {
        $info = [];

        foreach ($this->providers as $name => $providerClass) {
            try {
                $provider = $this->createProvider($name);
                $info[$name] = [
                    'name' => $provider->getName(),
                    'available' => $provider->isAvailable(),
                    'supported_currencies' => $provider->getSupportedCurrencies(),
                    'minimum_amount' => $provider->getMinimumAmount(),
                    'maximum_amount' => $provider->getMaximumAmount(),
                ];
            } catch (\Exception $e) {
                $info[$name] = [
                    'name' => $name,
                    'available' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $info;
    }

    /**
     * Get payment configuration summary
     *
     * @return array<string, mixed>
     */
    public function getConfiguration(): array
    {
        return [
            'providers' => $this->getAllProvidersInfo(),
            'supported_currencies' => $this->getSupportedCurrencies(),
            'amount_limits' => [
                'minimum' => $this->getMinimumAmount(),
                'maximum' => $this->getMaximumAmount(),
            ],
            'default_provider' => $this->defaultProvider,
        ];
    }

    /**
     * Process payment with fallback providers
     *
     * @param  array<string>|null  $preferredProviders
     * @return array<string, mixed>
     */
    public function processPaymentWithFallback(User $user, float $amount, string $reference, ?array $preferredProviders = null): array
    {
        $providers = $preferredProviders ?? ['paystack', 'flutterwave'];

        foreach ($providers as $providerName) {
            try {
                if ($this->isProviderAvailable($providerName)) {
                    $result = $this->initializePayment($user, $amount, $reference, $providerName);

                    if ($result['status'] === 'success') {
                        return $result;
                    }
                }
            } catch (\Exception $e) {
                Log::warning("Provider {$providerName} failed, trying next", [
                    'error' => $e->getMessage(),
                    'user_id' => $user->id,
                    'amount' => $amount,
                ]);

                continue;
            }
        }

        return [
            'status' => 'error',
            'error' => 'All payment providers failed',
        ];
    }

    /**
     * Process cashback with fallback providers
     *
     * @param  array<string>|null  $preferredProviders
     * @return array<string, mixed>
     */
    public function processCashbackWithFallback(User $user, float $amount, ?array $preferredProviders = null): array
    {
        $providers = $preferredProviders ?? ['paystack', 'flutterwave'];

        foreach ($providers as $providerName) {
            try {
                if ($this->isProviderAvailable($providerName)) {
                    $result = $this->processCashback($user, $amount, $providerName);

                    if ($result['status'] === 'success') {
                        return $result;
                    }
                }
            } catch (\Exception $e) {
                Log::warning("Provider {$providerName} failed, trying next", [
                    'error' => $e->getMessage(),
                    'user_id' => $user->id,
                    'amount' => $amount,
                ]);

                continue;
            }
        }

        return [
            'status' => 'error',
            'error' => 'All cashback providers failed',
        ];
    }

    /**
     * Register a new provider dynamically
     */
    public function registerProvider(string $name, string $providerClass): void
    {
        $this->providers[$name] = $providerClass;

        Log::info('New payment provider registered', [
            'name' => $name,
            'class' => $providerClass,
        ]);
    }

    /**
     * Unregister a provider
     */
    public function unregisterProvider(string $name): void
    {
        unset($this->providers[$name]);

        Log::info('Payment provider unregistered', [
            'name' => $name,
        ]);
    }
}
