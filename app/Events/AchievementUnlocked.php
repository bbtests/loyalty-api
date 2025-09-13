<?php

namespace App\Events;

use App\Models\Achievement;
use App\Models\User;
use Illuminate\Broadcasting\PrivateChannel;

class AchievementUnlocked extends BaseBroadcastEvent
{
    public User $user;

    public Achievement $achievement;

    public function __construct(User $user, Achievement $achievement)
    {
        parent::__construct();
        $this->user = $user;
        $this->achievement = $achievement;
    }

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.'.$this->user->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'achievement.unlocked';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'achievement' => [
                'id' => $this->achievement->id,
                'name' => $this->achievement->name,
                'description' => $this->achievement->description,
                'badge_icon' => $this->achievement->badge_icon,
            ],
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ],
        ];
    }
}
