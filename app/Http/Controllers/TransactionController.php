<?php

namespace App\Http\Controllers;

use App\Http\Requests\Transaction\ProcessTransactionRequest;
use App\Http\Resources\TransactionResource;
use App\Models\Transaction;
use App\Models\User;
use App\Services\LoyaltyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TransactionController extends Controller
{
    private LoyaltyService $loyaltyService;

    public function __construct(LoyaltyService $loyaltyService)
    {
        $this->loyaltyService = $loyaltyService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $transactions = Transaction::with(['user', 'cashbackPayment'])->get();

        return response()->json([
            'status' => 'success',
            'code' => 200,
            'message' => 'Transactions retrieved successfully',
            'data' => [
                'items' => $transactions,
            ],
        ]);
    }

    /**
     * Process a purchase transaction
     * POST /api/transactions
     */
    public function store(ProcessTransactionRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            $user = User::findOrFail($validated['user_id']);

            $transaction = $this->loyaltyService->processPurchase(
                $user,
                $validated['amount'],
                $validated['external_transaction_id'] ?? null
            );

            return response()->json([
                'success' => true,
                'message' => 'Transaction processed successfully',
                'data' => new TransactionResource($transaction),
            ], 201);

        } catch (\Exception $e) {
            Log::error('Transaction processing failed', [
                'request_data' => $request->all(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to process transaction',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Transaction $transaction): JsonResponse
    {
        $transaction->load(['user', 'cashbackPayment']);

        return response()->json([
            'status' => 'success',
            'code' => 200,
            'message' => 'Transaction retrieved successfully',
            'data' => [
                'item' => $transaction,
            ],
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Transaction $transaction): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'sometimes|exists:users,id',
            'amount' => 'sometimes|numeric|min:0',
            'points_earned' => 'sometimes|integer|min:0',
            'transaction_type' => 'sometimes|string|max:255',
            'external_transaction_id' => 'sometimes|string|max:255',
            'status' => 'sometimes|string|max:255',
            'metadata' => 'sometimes|array',
        ]);

        $transaction->update($validated);

        return response()->json([
            'status' => 'success',
            'code' => 200,
            'message' => 'Transaction updated successfully',
            'data' => [
                'item' => $transaction,
            ],
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Transaction $transaction): JsonResponse
    {
        $transaction->delete();

        return response()->json([
            'status' => 'success',
            'code' => 200,
            'message' => 'Transaction deleted successfully',
            'data' => [],
        ]);
    }

    /**
     * Handle payment provider webhooks
     * POST /api/webhooks/payment
     */
    public function handlePaymentWebhook(Request $request): JsonResponse
    {
        try {
            // Verify webhook signature (implementation depends on provider)
            $this->verifyWebhookSignature($request);

            $payload = $request->all();

            // Process based on event type
            switch ($payload['event'] ?? null) {
                case 'charge.success':
                    $this->handleSuccessfulPayment($payload);
                    break;

                case 'transfer.success':
                    $this->handleSuccessfulTransfer($payload);
                    break;

                default:
                    Log::info('Unhandled webhook event', ['payload' => $payload]);
            }

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            Log::error('Webhook processing failed', [
                'payload' => $request->all(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Webhook processing failed',
            ], 500);
        }
    }

    private function verifyWebhookSignature(Request $request): void
    {
        // Implementation depends on payment provider
        // For Paystack, verify using hash_hmac
        $signature = $request->header('x-paystack-signature');
        $payload = $request->getContent();
        $secret = config('payment.providers.paystack.secret_key');

        if ($signature !== hash_hmac('sha512', $payload, $secret)) {
            throw new \Exception('Invalid webhook signature');
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function handleSuccessfulPayment(array $payload): void
    {
        // Extract transaction details and process loyalty points
        $externalId = $payload['data']['reference'] ?? null;
        $amount = ($payload['data']['amount'] ?? 0) / 100; // Convert from kobo
        $customerEmail = $payload['data']['customer']['email'] ?? null;

        if ($customerEmail && $amount > 0) {
            $user = User::where('email', $customerEmail)->first();
            if ($user) {
                $this->loyaltyService->processPurchase($user, $amount, $externalId);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function handleSuccessfulTransfer(array $payload): void
    {
        // Handle successful cashback transfer
        $transferCode = $payload['data']['transfer_code'] ?? null;

        if ($transferCode) {
            // Update cashback payment status
            DB::table('cashback_payments')
                ->where('provider_transaction_id', $transferCode)
                ->update(['status' => 'completed']);
        }
    }
}
