<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property string $name
 * @property string $description
 * @property int $points_required
 * @property string $badge_icon
 * @property bool $is_active
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Achievement extends Model
{
    /** @use HasFactory<\Database\Factories\AchievementFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'points_required',
        'badge_icon',
        'is_active',
        'criteria',
    ];

    protected $casts = [
        'points_required' => 'integer',
        'is_active' => 'boolean',
        'criteria' => 'array',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany<\App\Models\User, $this, \Illuminate\Database\Eloquent\Relations\Pivot, 'pivot'>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_achievements')
            ->withTimestamps()
            ->withPivot('unlocked_at');
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<Achievement>  $query
     * @return \Illuminate\Database\Eloquent\Builder<Achievement>
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
