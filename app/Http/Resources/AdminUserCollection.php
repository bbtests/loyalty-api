<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class AdminUserCollection extends ResourceCollection
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'users' => $this->collection->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'points' => [
                        'available' => $user->loyaltyPoints->points ?? 0,
                        'total_earned' => $user->loyaltyPoints->total_earned ?? 0,
                    ],
                    'achievements_count' => $user->achievements->count(),
                    'current_badge' => $user->badges->first() ? [
                        'name' => $user->badges->first()->name,
                        'tier' => $user->badges->first()->tier,
                        'icon' => $user->badges->first()->icon,
                    ] : null,
                    'member_since' => $user->created_at->format('Y-m-d'),
                    'last_activity' => $user->updated_at->format('Y-m-d H:i:s'),
                ];
            }),
        ];
    }
}
