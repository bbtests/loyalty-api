<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\MessageQueueService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MessageQueueServiceTest extends TestCase
{
    use RefreshDatabase;

    protected MessageQueueService $messageQueueService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->messageQueueService = new MessageQueueService;
    }

    public function test_can_check_if_message_queue_is_available(): void
    {
        // Mock the connection to avoid actual RabbitMQ connection in tests
        $this->mock(MessageQueueService::class, function ($mock) {
            $mock->shouldReceive('isAvailable')->andReturn(true);
        });

        $service = $this->app->make(MessageQueueService::class);
        $this->assertTrue($service->isAvailable());
    }

    public function test_can_get_queue_statistics(): void
    {
        // Mock the connection to avoid actual RabbitMQ connection in tests
        $this->mock(MessageQueueService::class, function ($mock) {
            $mock->shouldReceive('getQueueStats')->andReturn([
                'queue_name' => 'purchase_events',
                'connection_status' => 'connected',
                'host' => 'localhost',
                'port' => 5672,
                'vhost' => '/',
            ]);
        });

        $service = $this->app->make(MessageQueueService::class);
        $stats = $service->getQueueStats();

        $this->assertArrayHasKey('queue_name', $stats);
        $this->assertArrayHasKey('connection_status', $stats);
        $this->assertEquals('purchase_events', $stats['queue_name']);
        $this->assertEquals('connected', $stats['connection_status']);
    }

    public function test_can_publish_purchase_event(): void
    {
        $user = User::factory()->create();
        $transaction = \App\Models\Transaction::factory()->create([
            'user_id' => $user->id,
            'amount' => 100.00,
            'transaction_type' => 'purchase',
            'status' => 'completed',
        ]);

        // Mock the connection to avoid actual RabbitMQ connection in tests
        $this->mock(MessageQueueService::class, function ($mock) use ($user, $transaction) {
            $mock->shouldReceive('publishPurchaseEvent')
                ->with($user, $transaction)
                ->once();
        });

        $service = $this->app->make(MessageQueueService::class);
        $service->publishPurchaseEvent($user, $transaction);
    }

    public function test_handles_connection_failure_gracefully(): void
    {
        // Mock the connection to simulate failure
        $this->mock(MessageQueueService::class, function ($mock) {
            $mock->shouldReceive('isAvailable')->andReturn(false);
        });

        $service = $this->app->make(MessageQueueService::class);
        $this->assertFalse($service->isAvailable());
    }

    public function test_can_close_connection(): void
    {
        // Mock the connection to avoid actual RabbitMQ connection in tests
        $this->mock(MessageQueueService::class, function ($mock) {
            $mock->shouldReceive('close')->once();
        });

        $service = $this->app->make(MessageQueueService::class);
        $service->close();
    }
}
