<?php

namespace App\Events;

use App\Models\Badge;
use App\Models\User;
use Illuminate\Broadcasting\PrivateChannel;

class BadgeUnlocked extends BaseBroadcastEvent
{
    public User $user;

    public Badge $badge;

    public function __construct(User $user, Badge $badge)
    {
        parent::__construct();
        $this->user = $user;
        $this->badge = $badge;
    }

    /**
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.'.$this->user->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'badge.unlocked';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'badge' => [
                'id' => $this->badge->id,
                'name' => $this->badge->name,
                'description' => $this->badge->description,
                'icon' => $this->badge->icon,
                'tier' => $this->badge->tier,
            ],
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ],
        ];
    }
}
