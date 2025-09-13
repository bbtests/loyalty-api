<?php

declare(strict_types=1);

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
    public function index(Request $request): JsonResponse
    {
        try {
            $pagination = $this::applyPagination($request);

            $transactions = Transaction::with(['user', 'cashbackPayment'])
                ->orderBy($pagination['sort_by'], $pagination['sort_order'])
                ->paginate($pagination['per_page']);

            return $this->successCollection(
                $transactions,
                TransactionResource::class,
                'Transactions retrieved successfully.',
                $this->buildFilters([
                    'search_query' => $request->input('search'),
                ])
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve transactions', 422, [
                $e->getMessage(),
            ]);
        }
    }

    /**
     * Get transactions for a specific user
     * GET /api/users/{user}/transactions
     */
    public function getUserTransactions(Request $request, User $user): JsonResponse
    {
        try {
            $pagination = $this::applyPagination($request);

            $transactions = Transaction::where('user_id', $user->id)
                ->with(['cashbackPayment'])
                ->orderBy($pagination['sort_by'], $pagination['sort_order'])
                ->paginate($pagination['per_page']);

            return $this->successCollection(
                $transactions,
                TransactionResource::class,
                'User transactions retrieved successfully.',
                $this->buildFilters([
                    'user_id' => $user->id,
                    'search_query' => $request->input('search'),
                ])
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve user transactions', 422, [
                $e->getMessage(),
            ]);
        }
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

            return $this->successItem(
                new TransactionResource($transaction),
                'Transaction processed successfully.',
                201,
                []
            );

        } catch (\Exception $e) {
            Log::error('Transaction processing failed', [
                'request_data' => $request->all(),
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse('Failed to process transaction', 422, [
                $e->getMessage(),
            ]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        try {
            $transaction = Transaction::with(['user', 'cashbackPayment'])->find($id);

            if (! $transaction) {
                return $this->notFoundError('Transaction', $id);
            }

            return $this->successItem(
                new TransactionResource($transaction),
                'Transaction retrieved successfully.'
            );

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve transaction', 422, [
                $e->getMessage(),
            ]);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $transaction = Transaction::find($id);

            if (! $transaction) {
                return $this->notFoundError('Transaction', $id);
            }

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

            return $this->successItem(
                new TransactionResource($transaction),
                'Transaction updated successfully.'
            );

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update transaction', 422, [
                $e->getMessage(),
            ]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $transaction = Transaction::find($id);

            if (! $transaction) {
                return $this->notFoundError('Transaction', $id);
            }

            $transaction->delete();

            return $this->successMessage('Transaction deleted successfully.');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete transaction', 422, [
                $e->getMessage(),
            ]);
        }
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

            return $this->successMessage('Webhook processed successfully.');

        } catch (\Exception $e) {
            Log::error('Webhook processing failed', [
                'payload' => $request->all(),
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse('Webhook processing failed', 500, [
                [
                    'field' => 'webhook',
                    'message' => 'Webhook processing failed due to an internal error.',
                ],
            ]);
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
