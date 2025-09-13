<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\User
 */
class AdminUserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'points' => [
                'available' => $this->loyaltyPoints->points ?? 0,
                'total_earned' => $this->loyaltyPoints->total_earned ?? 0,
            ],
            'achievements_count' => $this->achievements->count(),
            'current_badge' => $this->badges->first() ? [
                'name' => $this->badges->first()->name,
                'tier' => $this->badges->first()->tier,
                'icon' => $this->badges->first()->icon,
            ] : null,
            'member_since' => $this->created_at->format('Y-m-d'),
            'last_activity' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
