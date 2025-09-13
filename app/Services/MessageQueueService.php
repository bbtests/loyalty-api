<?php

namespace App\Services;

use App\Events\PurchaseProcessed;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class MessageQueueService
{
    private ?AMQPStreamConnection $connection = null;

    private ?AMQPChannel $channel = null;

    private string $host;

    private int $port;

    private string $user;

    private string $password;

    private string $vhost;

    public function __construct()
    {
        $this->host = config('queue.connections.rabbitmq.host', 'localhost');
        $this->port = config('queue.connections.rabbitmq.port', 5672);
        $this->user = config('queue.connections.rabbitmq.user', 'bumpa');
        $this->password = config('queue.connections.rabbitmq.password', 'bumpa123');
        $this->vhost = config('queue.connections.rabbitmq.vhost', '/');
    }

    /**
     * Initialize RabbitMQ connection
     */
    private function connect(): void
    {
        if ($this->connection === null || ! $this->connection->isConnected()) {
            try {
                $this->connection = new AMQPStreamConnection(
                    $this->host,
                    $this->port,
                    $this->user,
                    $this->password,
                    $this->vhost
                );

                $this->channel = $this->connection->channel();

                Log::info('RabbitMQ connection established', [
                    'host' => $this->host,
                    'port' => $this->port,
                    'vhost' => $this->vhost,
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to connect to RabbitMQ', [
                    'error' => $e->getMessage(),
                    'host' => $this->host,
                    'port' => $this->port,
                ]);
                throw $e;
            }
        }
    }

    /**
     * Publish purchase event to message queue
     */
    public function publishPurchaseEvent(User $user, Transaction $transaction): void
    {
        try {
            $this->connect();

            $exchange = config('queue.connections.rabbitmq.exchange', 'default');
            $queue = config('queue.connections.rabbitmq.queue', 'default');

            // Declare exchange and queue
            $this->channel->exchange_declare($exchange, 'direct', false, true, false);
            $this->channel->queue_declare($queue, false, true, false, false);
            $this->channel->queue_bind($queue, $exchange, 'purchase.processed');

            // Prepare message data
            $messageData = [
                'event_type' => 'purchase.processed',
                'user_id' => $user->id,
                'transaction_id' => $transaction->id,
                'amount' => $transaction->amount,
                'points_earned' => $transaction->points_earned,
                'timestamp' => now()->toISOString(),
                'metadata' => [
                    'transaction_type' => $transaction->transaction_type,
                    'external_transaction_id' => $transaction->external_transaction_id,
                ],
            ];

            $message = new AMQPMessage(
                json_encode($messageData),
                [
                    'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
                    'content_type' => 'application/json',
                ]
            );

            // Publish message
            $this->channel->basic_publish($message, $exchange, 'purchase.processed');

            Log::info('Purchase event published to message queue', [
                'user_id' => $user->id,
                'transaction_id' => $transaction->id,
                'exchange' => $exchange,
                'queue' => $queue,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to publish purchase event to message queue', [
                'user_id' => $user->id,
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
            ]);

            // Fallback to direct event dispatch
            event(new PurchaseProcessed($user, $transaction));
        }
    }

    /**
     * Consume purchase events from message queue
     */
    public function consumePurchaseEvents(): void
    {
        try {
            $this->connect();

            $exchange = config('queue.connections.rabbitmq.exchange', 'default');
            $queue = config('queue.connections.rabbitmq.queue', 'default');

            // Declare exchange and queue
            $this->channel->exchange_declare($exchange, 'direct', false, true, false);
            $this->channel->queue_declare($queue, false, true, false, false);
            $this->channel->queue_bind($queue, $exchange, 'purchase.processed');

            // Set QoS
            $this->channel->basic_qos(0, 1, null);

            // Set up consumer
            $callback = function ($msg) {
                try {
                    $data = json_decode($msg->body, true);

                    Log::info('Processing purchase event from message queue', [
                        'event_type' => $data['event_type'] ?? 'unknown',
                        'user_id' => $data['user_id'] ?? null,
                        'transaction_id' => $data['transaction_id'] ?? null,
                    ]);

                    // Process the event
                    $this->processPurchaseEvent($data);

                    // Acknowledge message
                    $msg->ack();

                } catch (\Exception $e) {
                    Log::error('Failed to process purchase event from message queue', [
                        'error' => $e->getMessage(),
                        'message_body' => $msg->body,
                    ]);

                    // Reject message
                    $msg->nack(false, false);
                }
            };

            $this->channel->basic_consume($queue, '', false, false, false, false, $callback);

            Log::info('Started consuming purchase events from message queue', [
                'exchange' => $exchange,
                'queue' => $queue,
            ]);

            // Keep consuming
            while ($this->channel->is_consuming()) {
                $this->channel->wait();
            }

        } catch (\Exception $e) {
            Log::error('Failed to consume purchase events from message queue', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Process purchase event data
     *
     * @param  array<string, mixed>  $data
     */
    private function processPurchaseEvent(array $data): void
    {
        $user = User::find($data['user_id']);
        $transaction = Transaction::find($data['transaction_id']);

        if (! $user || ! $transaction) {
            Log::warning('User or transaction not found for purchase event', [
                'user_id' => $data['user_id'] ?? null,
                'transaction_id' => $data['transaction_id'] ?? null,
            ]);

            return;
        }

        // Dispatch the event for achievement/badge processing
        event(new PurchaseProcessed($user, $transaction));
    }

    /**
     * Close RabbitMQ connection
     */
    public function close(): void
    {
        if ($this->channel) {
            $this->channel->close();
        }

        if ($this->connection) {
            $this->connection->close();
        }

        Log::info('RabbitMQ connection closed');
    }

    /**
     * Check if RabbitMQ is available
     */
    public function isAvailable(): bool
    {
        try {
            $this->connect();

            return true;
        } catch (\Exception $e) {
            Log::warning('RabbitMQ is not available', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get queue statistics
     *
     * @return array<string, mixed>
     */
    public function getQueueStats(): array
    {
        try {
            $this->connect();

            $queue = config('queue.connections.rabbitmq.queue', 'purchase_events');

            // Declare queue to get stats
            $this->channel->queue_declare($queue, false, true, false, false);

            return [
                'queue_name' => $queue,
                'connection_status' => 'connected',
                'host' => $this->host,
                'port' => $this->port,
                'vhost' => $this->vhost,
            ];

        } catch (\Exception $e) {
            return [
                'queue_name' => config('queue.connections.rabbitmq.queue', 'purchase_events'),
                'connection_status' => 'disconnected',
                'error' => $e->getMessage(),
            ];
        }
    }
}
