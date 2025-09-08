<?php

namespace App\Http\Controllers;

use App\Http\Requests\CashbackPayment\ProcessCashbackRequest;
use App\Jobs\ProcessCashbackPayment;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class CashbackController extends Controller
{
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

            // Validate cashback eligibility
            if (! $this->isEligibleForCashback($user, $amount)) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not eligible for cashback or invalid amount',
                ], 400);
            }

            // Queue the cashback payment job
            ProcessCashbackPayment::dispatch($user, $amount, $transactionId);

            return response()->json([
                'success' => true,
                'message' => 'Cashback payment queued for processing',
                'data' => [
                    'amount' => $amount,
                    'status' => 'queued',
                    'estimated_processing_time' => '5-10 minutes',
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to process cashback',
                'error' => $e->getMessage(),
            ], 500);
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

        // Maximum cashback per transaction
        $maxCashback = $totalSpent * (config('loyalty.cashback_percentage', 2) / 100);

        return $totalSpent >= $minimumSpending && $amount <= $maxCashback;
    }
}
