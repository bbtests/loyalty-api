<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $user_id
 * @property int $points
 * @property int $total_earned
 * @property int $total_redeemed
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class LoyaltyPoint extends Model
{
    /** @use HasFactory<\Database\Factories\LoyaltyPointFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'points',
        'total_earned',
        'total_redeemed',
    ];

    protected $casts = [
        'points' => 'integer',
        'total_earned' => 'integer',
        'total_redeemed' => 'integer',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function addPoints(int $points): void
    {
        $this->increment('points', $points);
        $this->increment('total_earned', $points);
    }

    public function redeemPoints(int $points): bool
    {
        if ($this->points < $points) {
            return false;
        }

        $this->decrement('points', $points);
        $this->increment('total_redeemed', $points);

        return true;
    }
}
