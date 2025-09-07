<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    private string $provider;

    private string $secretKey;

    private string $publicKey;

    private string $baseUrl;

    public function __construct()
    {
        $this->provider = config('payment.default_provider', 'paystack');
        $this->secretKey = config("payment.providers.{$this->provider}.secret_key");
        $this->publicKey = config("payment.providers.{$this->provider}.public_key");
        $this->baseUrl = config("payment.providers.{$this->provider}.base_url");
    }

    public function getPublicKey(): string
    {
        return $this->publicKey;
    }

    /**
     * @return array<string, mixed>
     */
    public function processCashback(User $user, float $amount): array
    {
        switch ($this->provider) {
            case 'paystack':
                return $this->processPaystackCashback($user, $amount);
            case 'flutterwave':
                return $this->processFlutterwaveCashback($user, $amount);
            default:
                return $this->processMockCashback($user, $amount);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function processPaystackCashback(User $user, float $amount): array
    {
        try {
            // First, ensure user has a transfer recipient
            $recipientCode = $this->getOrCreatePaystackRecipient($user);

            if (! $recipientCode) {
                throw new \Exception('Failed to create transfer recipient');
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$this->secretKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl.'/transfer', [
                'source' => 'balance',
                'amount' => $amount * 100, // Convert to kobo
                'recipient' => $recipientCode,
                'reason' => 'Loyalty Program Cashback',
                'reference' => 'cashback_'.uniqid().'_'.$user->id,
                'currency' => 'NGN',
            ]);

            if ($response->successful()) {
                $data = $response->json();

                Log::info('Paystack cashback successful', [
                    'user_id' => $user->id,
                    'amount' => $amount,
                    'transfer_code' => $data['data']['transfer_code'],
                ]);

                return [
                    'status' => 'completed',
                    'transaction_id' => $data['data']['transfer_code'],
                    'provider_response' => $data,
                    'reference' => $data['data']['reference'],
                ];
            }

            throw new \Exception('Payment failed: '.$response->body());
        } catch (\Exception $e) {
            Log::error('Paystack cashback failed', [
                'user_id' => $user->id,
                'amount' => $amount,
                'error' => $e->getMessage(),
            ]);

            return [
                'status' => 'failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function processFlutterwaveCashback(User $user, float $amount): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$this->secretKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl.'/transfers', [
                'account_bank' => $user->bank_code ?? '044', // Default to Access Bank
                'account_number' => $user->account_number ?? '0123456789',
                'amount' => $amount,
                'narration' => 'Loyalty Program Cashback',
                'currency' => 'NGN',
                'reference' => 'fw_cashback_'.uniqid().'_'.$user->id,
                'callback_url' => config('app.url').'/api/webhooks/flutterwave',
                'debit_currency' => 'NGN',
            ]);

            if ($response->successful()) {
                $data = $response->json();

                if ($data['status'] === 'success') {
                    Log::info('Flutterwave cashback initiated', [
                        'user_id' => $user->id,
                        'amount' => $amount,
                        'transfer_id' => $data['data']['id'],
                    ]);

                    return [
                        'status' => 'pending',
                        'transaction_id' => $data['data']['id'],
                        'provider_response' => $data,
                        'reference' => $data['data']['reference'],
                    ];
                }
            }

            throw new \Exception('Flutterwave transfer failed: '.$response->body());
        } catch (\Exception $e) {
            Log::error('Flutterwave cashback failed', [
                'user_id' => $user->id,
                'amount' => $amount,
                'error' => $e->getMessage(),
            ]);

            return [
                'status' => 'failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function processMockCashback(User $user, float $amount): array
    {
        // Simulate different scenarios for testing
        $scenarios = [
            ['status' => 'completed', 'probability' => 70],
            ['status' => 'pending', 'probability' => 20],
            ['status' => 'failed', 'probability' => 10],
        ];

        $random = rand(1, 100);
        $cumulative = 0;
        $selectedStatus = 'completed';

        foreach ($scenarios as $scenario) {
            $cumulative += $scenario['probability'];
            if ($random <= $cumulative) {
                $selectedStatus = $scenario['status'];
                break;
            }
        }

        if ($selectedStatus === 'failed') {
            return [
                'status' => 'failed',
                'error' => 'Mock payment failure for testing - insufficient balance',
            ];
        }

        return [
            'status' => $selectedStatus,
            'transaction_id' => 'mock_'.uniqid(),
            'provider_response' => [
                'mock' => true,
                'amount' => $amount,
                'user_id' => $user->id,
                'processed_at' => now(),
                'scenario' => $selectedStatus,
            ],
            'reference' => 'mock_ref_'.uniqid(),
        ];
    }

    private function getOrCreatePaystackRecipient(User $user): ?string
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

    /**
     * @return array<string, mixed>
     */
    public function initializePayment(User $user, float $amount, string $reference): array
    {
        switch ($this->provider) {
            case 'paystack':
                return $this->initializePaystackPayment($user, $amount, $reference);
            case 'flutterwave':
                return $this->initializeFlutterwavePayment($user, $amount, $reference);
            default:
                return $this->initializeMockPayment($user, $amount, $reference);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function initializePaystackPayment(User $user, float $amount, string $reference): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$this->secretKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl.'/transaction/initialize', [
                'amount' => $amount * 100, // Convert to kobo
                'email' => $user->email,
                'reference' => $reference,
                'callback_url' => config('app.url').'/payment/callback',
                'metadata' => [
                    'user_id' => $user->id,
                    'loyalty_program' => true,
                ],
            ]);

            if ($response->successful()) {
                $data = $response->json();

                return [
                    'status' => 'success',
                    'authorization_url' => $data['data']['authorization_url'],
                    'access_code' => $data['data']['access_code'],
                    'reference' => $data['data']['reference'],
                ];
            }

            throw new \Exception('Payment initialization failed: '.$response->body());
        } catch (\Exception $e) {
            Log::error('Paystack payment initialization failed', [
                'user_id' => $user->id,
                'amount' => $amount,
                'error' => $e->getMessage(),
            ]);

            return [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function initializeFlutterwavePayment(User $user, float $amount, string $reference): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$this->secretKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl.'/payments', [
                'tx_ref' => $reference,
                'amount' => $amount,
                'currency' => 'NGN',
                'redirect_url' => config('app.url').'/payment/callback',
                'customer' => [
                    'email' => $user->email,
                    'name' => $user->name,
                ],
                'customizations' => [
                    'title' => 'Loyalty Program Purchase',
                    'description' => 'Purchase with loyalty points earning',
                ],
                'meta' => [
                    'user_id' => $user->id,
                    'loyalty_program' => true,
                ],
            ]);

            if ($response->successful()) {
                $data = $response->json();

                return [
                    'status' => 'success',
                    'authorization_url' => $data['data']['link'],
                    'reference' => $reference,
                ];
            }

            throw new \Exception('Flutterwave payment initialization failed: '.$response->body());
        } catch (\Exception $e) {
            Log::error('Flutterwave payment initialization failed', [
                'user_id' => $user->id,
                'amount' => $amount,
                'error' => $e->getMessage(),
            ]);

            return [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function initializeMockPayment(User $user, float $amount, string $reference): array
    {
        return [
            'status' => 'success',
            'authorization_url' => config('app.url').'/payment/mock?reference='.$reference,
            'reference' => $reference,
            'mock' => true,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function verifyPayment(string $reference): array
    {
        switch ($this->provider) {
            case 'paystack':
                return $this->verifyPaystackPayment($reference);
            case 'flutterwave':
                return $this->verifyFlutterwavePayment($reference);
            default:
                return $this->verifyMockPayment($reference);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function verifyPaystackPayment(string $reference): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$this->secretKey,
            ])->get($this->baseUrl.'/transaction/verify/'.$reference);

            if ($response->successful()) {
                $data = $response->json();

                if ($data['data']['status'] === 'success') {
                    return [
                        'status' => 'success',
                        'amount' => $data['data']['amount'] / 100, // Convert from kobo
                        'reference' => $data['data']['reference'],
                        'customer_email' => $data['data']['customer']['email'],
                        'metadata' => $data['data']['metadata'],
                    ];
                }
            }

            return ['status' => 'failed', 'message' => 'Payment verification failed'];

        } catch (\Exception $e) {
            Log::error('Paystack payment verification failed', [
                'reference' => $reference,
                'error' => $e->getMessage(),
            ]);

            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function verifyFlutterwavePayment(string $reference): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$this->secretKey,
            ])->get($this->baseUrl.'/transactions/verify_by_reference?tx_ref='.$reference);

            if ($response->successful()) {
                $data = $response->json();

                if ($data['status'] === 'success' && $data['data']['status'] === 'successful') {
                    return [
                        'status' => 'success',
                        'amount' => $data['data']['amount'],
                        'reference' => $data['data']['tx_ref'],
                        'customer_email' => $data['data']['customer']['email'],
                        'metadata' => $data['data']['meta'],
                    ];
                }
            }

            return ['status' => 'failed', 'message' => 'Payment verification failed'];

        } catch (\Exception $e) {
            Log::error('Flutterwave payment verification failed', [
                'reference' => $reference,
                'error' => $e->getMessage(),
            ]);

            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function verifyMockPayment(string $reference): array
    {
        // Mock verification - always successful for testing
        return [
            'status' => 'success',
            'amount' => 100.00, // Mock amount
            'reference' => $reference,
            'customer_email' => 'test@example.com',
            'metadata' => ['mock' => true],
        ];
    }
}
