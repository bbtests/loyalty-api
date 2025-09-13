<?php

namespace App\Jobs;

use App\Models\CashbackPayment;
use App\Models\User;
use App\Services\PaymentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessCashbackPayment implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    private User $user;

    private float $amount;

    private ?int $transactionId;

    public function __construct(User $user, float $amount, ?int $transactionId = null)
    {
        $this->user = $user;
        $this->amount = $amount;
        $this->transactionId = $transactionId;

        // Set queue connection and name based on environment
        $this->onConnection(config('queue.default', 'redis'));
        $this->onQueue('default');
    }

    public function handle(PaymentService $paymentService): void
    {
        // Create cashback payment record
        $cashbackPayment = CashbackPayment::create([
            'user_id' => $this->user->id,
            'transaction_id' => $this->transactionId,
            'amount' => $this->amount,
            'payment_provider' => config('payment.default_provider', 'paystack'),
            'status' => 'pending',
        ]);

        try {
            // Process payment through provider
            $result = $paymentService->processCashback($this->user, $this->amount);

            // Update payment record with result
            $cashbackPayment->update([
                'provider_transaction_id' => $result['transaction_id'] ?? null,
                'status' => $result['status'] ?? 'failed',
                'payment_details' => $result,
            ]);

        } catch (\Exception $e) {
            $cashbackPayment->update([
                'status' => 'failed',
                'payment_details' => [
                    'error' => $e->getMessage(),
                    'failed_at' => now(),
                ],
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Cashback payment failed', [
            'user_id' => $this->user->id,
            'amount' => $this->amount,
            'error' => $exception->getMessage(),
            'queue_connection' => config('queue.default', 'redis'),
        ]);
    }
}
