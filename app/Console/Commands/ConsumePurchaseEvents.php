<?php

namespace App\Console\Commands;

use App\Services\MessageQueueService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ConsumePurchaseEvents extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'loyalty:consume-purchase-events';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Consume purchase events from message queue for loyalty program processing';

    /**
     * Execute the console command.
     */
    public function handle(MessageQueueService $messageQueueService): int
    {
        $this->info('Starting purchase event consumer...');

        try {
            // Check if message queue is available
            if (! $messageQueueService->isAvailable()) {
                $this->error('Message queue is not available. Please check RabbitMQ configuration.');

                return 1;
            }

            $this->info('Message queue is available. Starting to consume events...');

            // Start consuming events
            $messageQueueService->consumePurchaseEvents();

        } catch (\Exception $e) {
            $this->error('Failed to consume purchase events: '.$e->getMessage());
            Log::error('Purchase event consumer failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return 1;
        }

        return 0;
    }
}
