<?php

namespace App\Http\Resources\LoyaltyPoint;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\LoyaltyPoint
 */
class LoyaltyPointResource extends JsonResource
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
            'user_id' => $this->user_id,
            'points' => $this->points,
            'total_earned' => $this->total_earned,
            'total_redeemed' => $this->total_redeemed,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
