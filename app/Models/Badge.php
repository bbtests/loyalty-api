<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property string $name
 * @property string $description
 * @property array<string, mixed> $requirements
 * @property string $icon
 * @property int $tier
 * @property bool $is_active
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Badge extends Model
{
    /** @use HasFactory<\Database\Factories\BadgeFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'requirements',
        'icon',
        'tier',
        'is_active',
    ];

    protected $casts = [
        'requirements' => 'array',
        'tier' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany<\App\Models\User, $this, \Illuminate\Database\Eloquent\Relations\Pivot, 'pivot'>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_badges')
            ->withTimestamps()
            ->withPivot('earned_at');
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<Badge>  $query
     * @return \Illuminate\Database\Eloquent\Builder<Badge>
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<Badge>  $query
     * @return \Illuminate\Database\Eloquent\Builder<Badge>
     */
    public function scopeByTier($query, int $tier)
    {
        return $query->where('tier', $tier);
    }
}
