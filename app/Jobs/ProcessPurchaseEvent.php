<?php

namespace App\Jobs;

use App\Events\PurchaseProcessed;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessPurchaseEvent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $user_id;

    public int $transaction_id;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 30;

    /**
     * The maximum number of seconds the job can run.
     */
    public int $timeout = 120;

    public function __construct(int $user_id, int $transaction_id)
    {
        $this->user_id = $user_id;
        $this->transaction_id = $transaction_id;

        // Set queue connection and name based on environment
        $this->onConnection(config('queue.default', 'redis'));
        $this->onQueue('default');
    }

    public function handle(): void
    {
        Log::info('Processing purchase event job', [
            'user_id' => $this->user_id,
            'transaction_id' => $this->transaction_id,
            'queue_connection' => config('queue.default'),
        ]);

        $user = User::find($this->user_id);
        $transaction = Transaction::find($this->transaction_id);

        if (! $user || ! $transaction) {
            Log::warning('User or transaction not found for purchase event job', [
                'user_id' => $this->user_id,
                'transaction_id' => $this->transaction_id,
            ]);

            return;
        }

        // Dispatch the event for achievement/badge processing
        Log::info('Dispatching PurchaseProcessed event', [
            'user_id' => $this->user_id,
            'transaction_id' => $this->transaction_id,
        ]);

        event(new PurchaseProcessed($user, $transaction));

        Log::info('PurchaseProcessed event dispatched successfully', [
            'user_id' => $this->user_id,
            'transaction_id' => $this->transaction_id,
        ]);

        Log::info('Purchase event job completed successfully', [
            'user_id' => $this->user_id,
            'transaction_id' => $this->transaction_id,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessPurchaseEvent job failed', [
            'user_id' => $this->user_id,
            'transaction_id' => $this->transaction_id,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
