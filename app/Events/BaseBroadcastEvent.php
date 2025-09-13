<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

abstract class BaseBroadcastEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, Queueable, SerializesModels;

    public function __construct()
    {
        // Set queue connection and name using trait methods
        $this->onConnection(config('queue.default', 'redis'));
        $this->onQueue('default');
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    abstract public function broadcastOn(): array;

    /**
     * Get the event name to broadcast as.
     */
    abstract public function broadcastAs(): string;

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    abstract public function broadcastWith(): array;
}
