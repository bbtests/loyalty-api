<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LoyaltyStatsResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'overview' => [
                'total_users' => $this->resource['total_users'],
                'active_users' => $this->resource['active_users'],
                'engagement_rate' => $this->resource['active_users'] > 0
                    ? round(($this->resource['active_users'] / $this->resource['total_users']) * 100, 2)
                    : 0,
            ],
            'points' => [
                'total_issued' => $this->resource['total_points_issued'],
                'total_redeemed' => $this->resource['total_points_redeemed'],
                'redemption_rate' => $this->resource['total_points_issued'] > 0
                    ? round(($this->resource['total_points_redeemed'] / $this->resource['total_points_issued']) * 100, 2)
                    : 0,
            ],
            'transactions' => [
                'total_count' => $this->resource['total_transactions'],
                'total_revenue' => $this->resource['total_revenue'],
                'average_transaction' => $this->resource['total_transactions'] > 0
                    ? round($this->resource['total_revenue'] / $this->resource['total_transactions'], 2)
                    : 0,
            ],
            'achievements' => [
                'total_unlocked' => $this->resource['achievements_unlocked'],
                'badges_earned' => $this->resource['badges_earned'],
            ],
            'badge_distribution' => $this->resource['badge_distribution'],
            'recent_achievements' => $this->resource['recent_achievements'],
        ];
    }
}
