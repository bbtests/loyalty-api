<?php

namespace App\Services\Payment\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FlutterwaveProvider extends BasePaymentProvider
{
    private string $secretKey;

    private string $publicKey;

    private string $baseUrl;

    public function __construct(array $config)
    {
        parent::__construct($config, 'flutterwave');

        $this->secretKey = $config['secret_key'] ?? '';
        $this->publicKey = $config['public_key'] ?? '';
        $this->baseUrl = $config['base_url'] ?? 'https://api.flutterwave.com/v3';
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
            ])->post($this->baseUrl.'/payments', [
                'tx_ref' => $reference,
                'amount' => $amount,
                'currency' => 'NGN',
                'redirect_url' => $callbackUrl ?? config('constants.app.frontend_url').config('constants.urls.payment_callback'),
                'customer' => [
                    'email' => $user->email,
                    'name' => $user->name,
                ],
                'customizations' => [
                    'title' => 'Loyalty Program Payment',
                    'description' => 'Payment for loyalty program',
                ],
                'meta' => [
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
                    'authorization_url' => $data['data']['link'],
                    'reference' => $data['data']['tx_ref'],
                    'transaction_id' => $data['data']['id'],
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
            ])->get($this->baseUrl.'/transactions/'.$reference.'/verify');

            if ($response->successful()) {
                $data = $response->json();

                $this->logPayment('verify_payment', [
                    'reference' => $reference,
                ]);

                return $this->createResponse(true, [
                    'transaction_id' => $data['data']['id'],
                    'status' => $data['data']['status'],
                    'amount' => $data['data']['amount'],
                    'currency' => $data['data']['currency'],
                    'reference' => $data['data']['tx_ref'],
                    'customer' => $data['data']['customer'],
                    'meta' => $data['data']['meta'] ?? [],
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
            // Create transfer recipient first
            $recipientId = $this->getOrCreateRecipient($user);
            if (! $recipientId) {
                return $this->createResponse(false, [], 'Failed to create transfer recipient');
            }

            // Process transfer
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$this->secretKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl.'/transfers', [
                'account_bank' => $user->bank_code ?? '044', // Access Bank
                'account_number' => $user->account_number ?? '0123456789',
                'amount' => $amount,
                'narration' => 'Loyalty Program Cashback',
                'currency' => 'NGN',
                'reference' => $this->generateReference('cashback'),
                'beneficiary_name' => $user->name,
            ]);

            if ($response->successful()) {
                $data = $response->json();

                $this->logPayment('process_cashback', [
                    'user_id' => $user->id,
                    'amount' => $amount,
                    'recipient_id' => $recipientId,
                ]);

                return $this->createResponse(true, [
                    'transaction_id' => $data['data']['id'],
                    'reference' => $data['data']['reference'] ?? $this->generateReference('cashback'),
                    'status' => 'success',
                    'amount' => $amount,
                    'recipient_id' => $recipientId,
                ]);
            }

            $error = 'Cashback processing failed: '.$response->body();
            $this->logPayment('process_cashback', [
                'user_id' => $user->id,
                'amount' => $amount,
                'recipient_id' => $recipientId,
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
        return ['NGN', 'USD', 'GBP', 'EUR', 'ZAR', 'KES', 'GHS', 'UGX', 'TZS'];
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
     * Get or create Flutterwave transfer recipient
     */
    private function getOrCreateRecipient(User $user): ?string
    {
        // Check cache first
        $cacheKey = "flutterwave_recipient_{$user->id}";
        $cachedRecipient = Cache::get($cacheKey);

        if ($cachedRecipient) {
            return $cachedRecipient;
        }

        try {
            // Create transfer recipient
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$this->secretKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl.'/beneficiaries', [
                'account_bank' => $user->bank_code ?? '044', // Access Bank
                'account_number' => $user->account_number ?? '0123456789',
                'beneficiary_name' => $user->name,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $recipientId = $data['data']['id'];

                // Cache for 24 hours
                Cache::put($cacheKey, $recipientId, 86400);

                return $recipientId;
            }

            Log::error('Failed to create Flutterwave recipient', [
                'user_id' => $user->id,
                'response' => $response->body(),
            ]);

        } catch (\Exception $e) {
            Log::error('Exception creating Flutterwave recipient', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }
}
