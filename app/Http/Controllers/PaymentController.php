<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\PaymentService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    use ApiResponseTrait;

    private PaymentService $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Get payment configuration
     */
    public function getConfiguration(): JsonResponse
    {
        try {
            $config = $this->paymentService->getConfiguration();

            return $this->successData(
                ['item' => $config],
                'Payment configuration retrieved successfully.'
            );
        } catch (\Exception $e) {
            Log::error('Failed to get payment configuration', [
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse(
                'Failed to get payment configuration',
                500,
                [
                    [
                        'field' => 'configuration',
                        'message' => 'Payment configuration retrieval failed due to an internal error.',
                    ],
                ]
            );
        }
    }

    /**
     * Initialize payment
     */
    public function initializePayment(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:1',
            'provider' => 'nullable|string|in:paystack,flutterwave',
            'description' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse(
                'Validation failed',
                422,
                $validator->errors()->toArray()
            );
        }

        try {
            $user = Auth::user();
            if (! $user) {
                return $this->errorResponse(
                    'User not authenticated',
                    401,
                    [
                        [
                            'field' => 'authentication',
                            'message' => 'User must be authenticated to initialize payment.',
                        ],
                    ]
                );
            }

            $amount = $request->input('amount');
            $provider = $request->input('provider');
            $description = $request->input('description', 'Payment');

            // Generate reference
            $reference = 'pay_'.time().'_'.substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyz'), 0, 8);

            $result = $this->paymentService->initializePayment($user, $amount, $reference, $provider);

            // Add missing fields that the client expects
            $transactionData = array_merge($result, [
                'id' => time(), // Temporary ID
                'amount' => $amount,
                'description' => $description,
                'points_earned' => floor($amount * 10), // 10 points per dollar
                'status' => 'pending',
                'created_at' => now()->toISOString(),
            ]);

            return $this->successData(
                ['item' => $transactionData],
                'Payment initialized successfully.',
                200,
                [
                    'payment' => [
                        'provider' => $provider ?? 'default',
                        'amount' => $amount,
                        'reference' => $reference,
                    ],
                ]
            );

        } catch (\Exception $e) {
            Log::error('Payment initialization failed', [
                'user_id' => Auth::id(),
                'amount' => $request->input('amount'),
                'provider' => $request->input('provider'),
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse(
                'Payment initialization failed',
                500,
                [
                    [
                        'field' => 'payment',
                        'message' => 'Payment initialization failed due to an internal error.',
                    ],
                ]
            );
        }
    }

    /**
     * Verify payment
     */
    public function verifyPayment(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'reference' => 'required|string',
            'provider' => 'nullable|string|in:paystack,flutterwave',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse(
                'Validation failed',
                422,
                $validator->errors()->toArray()
            );
        }

        try {
            $reference = $request->input('reference');
            $provider = $request->input('provider');

            $result = $this->paymentService->verifyPayment($reference, $provider);

            return $this->successData(
                ['item' => $result],
                'Payment verification completed successfully.',
                200,
                [
                    'verification' => [
                        'reference' => $reference,
                        'provider' => $provider ?? 'default',
                    ],
                ]
            );

        } catch (\Exception $e) {
            Log::error('Payment verification failed', [
                'reference' => $request->input('reference'),
                'provider' => $request->input('provider'),
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse(
                'Payment verification failed',
                500,
                [
                    [
                        'field' => 'verification',
                        'message' => 'Payment verification failed due to an internal error.',
                    ],
                ]
            );
        }
    }

    /**
     * Process cashback
     */
    public function processCashback(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:1',
            'provider' => 'nullable|string|in:paystack,flutterwave',
            'description' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse(
                'Validation failed',
                422,
                $validator->errors()->toArray()
            );
        }

        try {
            $user = Auth::user();
            if (! $user) {
                return $this->errorResponse(
                    'User not authenticated',
                    401,
                    [
                        [
                            'field' => 'authentication',
                            'message' => 'User must be authenticated to process cashback.',
                        ],
                    ]
                );
            }

            $amount = $request->input('amount');
            $provider = $request->input('provider');
            $description = $request->input('description', 'Cashback');

            $result = $this->paymentService->processCashback($user, $amount, $provider);

            return $this->successData(
                ['item' => $result],
                'Cashback processed successfully.',
                200,
                [
                    'cashback' => [
                        'provider' => $provider ?? 'default',
                        'amount' => $amount,
                        'description' => $description,
                    ],
                ]
            );

        } catch (\Exception $e) {
            Log::error('Cashback processing failed', [
                'user_id' => Auth::id(),
                'amount' => $request->input('amount'),
                'provider' => $request->input('provider'),
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse(
                'Cashback processing failed',
                500,
                [
                    [
                        'field' => 'cashback',
                        'message' => 'Cashback processing failed due to an internal error.',
                    ],
                ]
            );
        }
    }

    /**
     * Get available providers
     */
    public function getProviders(): JsonResponse
    {
        try {
            $providers = $this->paymentService->getAllProvidersInfo();

            return $this->successData(
                ['item' => $providers],
                'Payment providers retrieved successfully.'
            );
        } catch (\Exception $e) {
            Log::error('Failed to get payment providers', [
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse(
                'Failed to get payment providers',
                500,
                [
                    [
                        'field' => 'providers',
                        'message' => 'Payment providers retrieval failed due to an internal error.',
                    ],
                ]
            );
        }
    }

    /**
     * Get public key for provider
     */
    public function getPublicKey(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'provider' => 'nullable|string|in:paystack,flutterwave',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse(
                'Validation failed',
                422,
                $validator->errors()->toArray()
            );
        }

        try {
            $provider = $request->input('provider');
            $publicKey = $this->paymentService->getPublicKey($provider);

            return $this->successData(
                [
                    'item' => [
                        'public_key' => $publicKey,
                        'provider' => $provider ?? 'default',
                    ],
                ],
                'Public key retrieved successfully.',
                200,
                [
                    'provider' => [
                        'name' => $provider ?? 'default',
                        'key_type' => 'public',
                    ],
                ]
            );
        } catch (\Exception $e) {
            Log::error('Failed to get public key', [
                'provider' => $request->input('provider'),
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse(
                'Failed to get public key',
                500,
                [
                    [
                        'field' => 'public_key',
                        'message' => 'Public key retrieval failed due to an internal error.',
                    ],
                ]
            );
        }
    }
}
