<?php

namespace App\Services\Payment\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaystackProvider extends BasePaymentProvider
{
    private string $secretKey;

    private string $publicKey;

    private string $baseUrl;

    public function __construct(array $config)
    {
        parent::__construct($config, 'paystack');

        $this->secretKey = $config['secret_key'] ?? '';
        $this->publicKey = $config['public_key'] ?? '';
        $this->baseUrl = $config['base_url'] ?? 'https://api.paystack.co';
    }

    public function initializePayment(User $user, float $amount, string $reference, ?string $callbackUrl = null): array
    {
        $validationError = $this->validatePaymentData($user, $amount);
        if ($validationError) {
            return $this->createResponse(false, [], $validationError);
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$this->secretKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl.'/transaction/initialize', [
                'amount' => $amount * 100, // Convert to kobo
                'email' => $user->email,
                'reference' => $reference,
                'callback_url' => $callbackUrl ?? config('constants.app.frontend_url').config('constants.urls.payment_callback'),
                'metadata' => [
                    'user_id' => $user->id,
                    'loyalty_program' => true,
                ],
            ]);

            if ($response->successful()) {
                $data = $response->json();

                $this->logPayment('initialize_payment', [
                    'user_id' => $user->id,
                    'amount' => $amount,
                    'reference' => $reference,
                ]);

                return $this->createResponse(true, [
                    'authorization_url' => $data['data']['authorization_url'],
                    'access_code' => $data['data']['access_code'],
                    'reference' => $data['data']['reference'],
                ]);
            }

            $error = 'Payment initialization failed: '.$response->body();
            $this->logPayment('initialize_payment', [
                'user_id' => $user->id,
                'amount' => $amount,
                'reference' => $reference,
            ], $error);

            return $this->createResponse(false, [], $error);

        } catch (\Exception $e) {
            $error = 'Payment initialization exception: '.$e->getMessage();
            $this->logPayment('initialize_payment', [
                'user_id' => $user->id,
                'amount' => $amount,
                'reference' => $reference,
            ], $error);

            return $this->createResponse(false, [], $error);
        }
    }

    public function verifyPayment(string $reference): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$this->secretKey,
                'Content-Type' => 'application/json',
            ])->get($this->baseUrl.'/transaction/verify/'.$reference);

            if ($response->successful()) {
                $data = $response->json();

                $this->logPayment('verify_payment', [
                    'reference' => $reference,
                ]);

                return $this->createResponse(true, [
                    'transaction_id' => $data['data']['id'],
                    'status' => $data['data']['status'],
                    'amount' => $data['data']['amount'] / 100, // Convert from kobo
                    'currency' => $data['data']['currency'],
                    'reference' => $data['data']['reference'],
                    'customer' => $data['data']['customer'],
                    'metadata' => $data['data']['metadata'] ?? [],
                ]);
            }

            $error = 'Payment verification failed: '.$response->body();
            $this->logPayment('verify_payment', [
                'reference' => $reference,
            ], $error);

            return $this->createResponse(false, [], $error);

        } catch (\Exception $e) {
            $error = 'Payment verification exception: '.$e->getMessage();
            $this->logPayment('verify_payment', [
                'reference' => $reference,
            ], $error);

            return $this->createResponse(false, [], $error);
        }
    }

    public function processCashback(User $user, float $amount): array
    {
        $validationError = $this->validatePaymentData($user, $amount);
        if ($validationError) {
            return $this->createResponse(false, [], $validationError);
        }

        try {
            // First, ensure user has a transfer recipient
            $recipientCode = $this->getOrCreateRecipient($user);
            if (! $recipientCode) {
                return $this->createResponse(false, [], 'Failed to create transfer recipient');
            }

            // Process transfer
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$this->secretKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl.'/transfer', [
                'source' => 'balance',
                'amount' => $amount * 100, // Convert to kobo
                'recipient' => $recipientCode,
                'reason' => 'Loyalty Program Cashback',
                'reference' => $this->generateReference('cashback'),
            ]);

            if ($response->successful()) {
                $data = $response->json();

                $this->logPayment('process_cashback', [
                    'user_id' => $user->id,
                    'amount' => $amount,
                    'recipient_code' => $recipientCode,
                ]);

                return $this->createResponse(true, [
                    'transaction_id' => $data['data']['transfer_code'],
                    'reference' => $data['data']['reference'] ?? $this->generateReference('cashback'),
                    'status' => 'success',
                    'amount' => $amount,
                    'recipient_code' => $recipientCode,
                ]);
            }

            $error = 'Cashback processing failed: '.$response->body();
            $this->logPayment('process_cashback', [
                'user_id' => $user->id,
                'amount' => $amount,
                'recipient_code' => $recipientCode,
            ], $error);

            return $this->createResponse(false, [], $error);

        } catch (\Exception $e) {
            $error = 'Cashback processing exception: '.$e->getMessage();
            $this->logPayment('process_cashback', [
                'user_id' => $user->id,
                'amount' => $amount,
            ], $error);

            return $this->createResponse(false, [], $error);
        }
    }

    public function isAvailable(): bool
    {
        return parent::isAvailable() && ! empty($this->secretKey) && ! empty($this->publicKey);
    }

    public function getSupportedCurrencies(): array
    {
        return ['NGN', 'USD', 'GBP', 'EUR', 'ZAR', 'KES', 'GHS'];
    }

    public function getMinimumAmount(): float
    {
        return 1.0; // 1 NGN minimum
    }

    public function getMaximumAmount(): float
    {
        return 10000000.0; // 10M NGN maximum
    }

    /**
     * Get or create Paystack transfer recipient
     */
    private function getOrCreateRecipient(User $user): ?string
    {
        // Check cache first
        $cacheKey = "paystack_recipient_{$user->id}";
        $cachedRecipient = Cache::get($cacheKey);

        if ($cachedRecipient) {
            return $cachedRecipient;
        }

        try {
            // Create transfer recipient
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$this->secretKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl.'/transferrecipient', [
                'type' => 'nuban',
                'name' => $user->name,
                'account_number' => $user->account_number ?? '0123456789', // Mock account
                'bank_code' => $user->bank_code ?? '044', // Access Bank
                'currency' => 'NGN',
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $recipientCode = $data['data']['recipient_code'];

                // Cache for 24 hours
                Cache::put($cacheKey, $recipientCode, 86400);

                return $recipientCode;
            }

            Log::error('Failed to create Paystack recipient', [
                'user_id' => $user->id,
                'response' => $response->body(),
            ]);

        } catch (\Exception $e) {
            Log::error('Exception creating Paystack recipient', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }
}
