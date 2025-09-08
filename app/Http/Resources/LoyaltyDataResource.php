<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LoyaltyDataResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'user_id' => $this->resource['user_id'],
            'points' => [
                'available' => $this->resource['points']['available'],
                'total_earned' => $this->resource['points']['total_earned'],
                'total_redeemed' => $this->resource['points']['total_redeemed'],
            ],
            'achievements' => $this->resource['achievements']->map(function ($achievement) {
                return [
                    'id' => $achievement['id'],
                    'name' => $achievement['name'],
                    'description' => $achievement['description'],
                    'badge_icon' => $achievement['badge_icon'],
                    'unlocked_at' => $achievement['unlocked_at'],
                ];
            }),
            'badges' => $this->resource['badges']->map(function ($badge) {
                return [
                    'id' => $badge['id'],
                    'name' => $badge['name'],
                    'description' => $badge['description'],
                    'icon' => $badge['icon'],
                    'tier' => $badge['tier'],
                    'earned_at' => $badge['earned_at'],
                ];
            }),
            'current_badge' => $this->resource['current_badge'] ? [
                'id' => $this->resource['current_badge']['id'],
                'name' => $this->resource['current_badge']['name'],
                'tier' => $this->resource['current_badge']['tier'],
                'icon' => $this->resource['current_badge']['icon'],
            ] : null,
        ];
    }
}
