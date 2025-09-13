<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\CashbackPayment\ProcessCashbackRequest;
use App\Jobs\ProcessCashbackPayment;
use App\Models\CashbackPayment;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CashbackPaymentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $pagination = $this::applyPagination($request);

            $cashbackPayments = CashbackPayment::with(['user', 'transaction'])
                ->orderBy($pagination['sort_by'], $pagination['sort_order'])
                ->paginate($pagination['per_page']);

            return $this->successCollection(
                $cashbackPayments,
                \App\Http\Resources\CashbackPayment\CashbackPaymentResource::class,
                'Cashback payments retrieved successfully.',
                $this->buildFilters([
                    'search_query' => $request->input('search'),
                ])
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve cashback payments', 422, [
                $e->getMessage(),
            ]);
        }
    }

    /**
     * Get cashback payments for a specific user
     * GET /api/users/{user}/cashback-payments
     */
    public function getUserCashbackPayments(Request $request, User $user): JsonResponse
    {
        try {
            $pagination = $this::applyPagination($request);

            $cashbackPayments = CashbackPayment::where('user_id', $user->id)
                ->with(['transaction'])
                ->orderBy($pagination['sort_by'], $pagination['sort_order'])
                ->paginate($pagination['per_page']);

            return $this->successCollection(
                $cashbackPayments,
                \App\Http\Resources\CashbackPayment\CashbackPaymentResource::class,
                'User cashback payments retrieved successfully.',
                $this->buildFilters([
                    'user_id' => $user->id,
                    'search_query' => $request->input('search'),
                ])
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve user cashback payments', 422, [
                $e->getMessage(),
            ]);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'user_id' => 'required|exists:users,id',
                'transaction_id' => 'required|exists:transactions,id',
                'amount' => 'required|numeric|min:0',
                'payment_provider' => 'required|string|max:255',
                'provider_transaction_id' => 'required|string|max:255',
                'status' => 'required|string|max:255',
                'payment_details' => 'sometimes|array',
            ]);

            $cashbackPayment = CashbackPayment::create($validated);

            return $this->successItem(
                new \App\Http\Resources\CashbackPayment\CashbackPaymentResource($cashbackPayment),
                'Cashback payment created successfully.',
                201,
                []
            );

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create cashback payment', 422, [
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
            $cashbackPayment = CashbackPayment::with(['user', 'transaction'])->find($id);

            if (! $cashbackPayment) {
                return $this->notFoundError('Cashback payment', $id);
            }

            return $this->successItem(
                new \App\Http\Resources\CashbackPayment\CashbackPaymentResource($cashbackPayment),
                'Cashback payment retrieved successfully.'
            );

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve cashback payment', 422, [
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
            $cashbackPayment = CashbackPayment::find($id);

            if (! $cashbackPayment) {
                return $this->notFoundError('Cashback payment', $id);
            }

            $validated = $request->validate([
                'user_id' => 'sometimes|exists:users,id',
                'transaction_id' => 'sometimes|exists:transactions,id',
                'amount' => 'sometimes|numeric|min:0',
                'payment_provider' => 'sometimes|string|max:255',
                'provider_transaction_id' => 'sometimes|string|max:255',
                'status' => 'sometimes|string|max:255',
                'payment_details' => 'sometimes|array',
            ]);

            $cashbackPayment->update($validated);

            return $this->successItem(
                new \App\Http\Resources\CashbackPayment\CashbackPaymentResource($cashbackPayment),
                'Cashback payment updated successfully.'
            );

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update cashback payment', 422, [
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
            $cashbackPayment = CashbackPayment::find($id);

            if (! $cashbackPayment) {
                return $this->notFoundError('Cashback payment', $id);
            }

            $cashbackPayment->delete();

            return $this->successMessage('Cashback payment deleted successfully.');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete cashback payment', 422, [
                $e->getMessage(),
            ]);
        }
    }

    /**
     * Process cashback payment
     * POST /api/cashback/process
     */
    public function process(ProcessCashbackRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            $user = $request->user();
            $amount = $validated['amount'];
            $transactionId = $validated['transaction_id'] ?? null;

            \Log::info('Processing cashback request', [
                'user_id' => $user->id,
                'amount' => $amount,
                'transaction_id' => $transactionId,
            ]);

            // Validate cashback eligibility
            if (! $this->isEligibleForCashback($user, $amount)) {
                \Log::warning('User not eligible for cashback', [
                    'user_id' => $user->id,
                    'amount' => $amount,
                ]);

                return $this->errorResponse(
                    'User not eligible for cashback or invalid amount',
                    400,
                    [
                        [
                            'field' => 'amount',
                            'message' => 'User does not meet cashback eligibility requirements',
                        ],
                    ]
                );
            }

            // Queue the cashback payment job
            ProcessCashbackPayment::dispatch($user, $amount, $transactionId);

            \Log::info('Cashback payment queued successfully', [
                'user_id' => $user->id,
                'amount' => $amount,
            ]);

            return $this->successData(
                [
                    'item' => [
                        'amount' => $amount,
                        'status' => 'queued',
                        'estimated_processing_time' => '5-10 minutes',
                        'transaction_id' => $transactionId,
                    ],
                ],
                'Cashback payment queued for processing',
                200,
                []
            );

        } catch (\Exception $e) {
            \Log::error('Failed to process cashback', [
                'user_id' => $request->user()?->id,
                'amount' => $validated['amount'] ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->errorResponse(
                'Failed to process cashback',
                500,
                [
                    [
                        'field' => 'system',
                        'message' => $e->getMessage(),
                    ],
                ]
            );
        }
    }

    private function isEligibleForCashback(User $user, float $amount): bool
    {
        // Check if user has sufficient transaction history
        $totalSpent = $user->transactions()
            ->where('transaction_type', 'purchase')
            ->sum('amount');

        // Minimum spending requirement for cashback
        $minimumSpending = 100;

        // Maximum cashback amount per request (reasonable limit)
        $maxCashback = $totalSpent * (config('loyalty.cashback_percentage', 2) / 100);

        $isEligible = $totalSpent >= $minimumSpending && $amount <= $maxCashback && $amount > 0;

        \Log::info('Cashback eligibility check', [
            'user_id' => $user->id,
            'total_spent' => $totalSpent,
            'requested_amount' => $amount,
            'minimum_spending' => $minimumSpending,
            'max_cashback_per_request' => $maxCashback,
            'is_eligible' => $isEligible,
        ]);

        return $isEligible;
    }
}
