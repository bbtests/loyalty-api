<?php

declare(strict_types=1);

namespace App\Models;

use App\Observers\UserObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

#[ObservedBy([UserObserver::class])]
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne<\App\Models\LoyaltyPoint, $this>
     */
    public function loyaltyPoints(): HasOne
    {
        return $this->hasOne(LoyaltyPoint::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany<\App\Models\Achievement, $this, \Illuminate\Database\Eloquent\Relations\Pivot, 'pivot'>
     */
    public function achievements(): BelongsToMany
    {
        return $this->belongsToMany(Achievement::class, 'user_achievements')
            ->withTimestamps()
            ->withPivot('unlocked_at');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany<\App\Models\Badge, $this, \Illuminate\Database\Eloquent\Relations\Pivot, 'pivot'>
     */
    public function badges(): BelongsToMany
    {
        return $this->belongsToMany(Badge::class, 'user_badges')
            ->withTimestamps()
            ->withPivot('earned_at');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Transaction, $this>
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\CashbackPayment, $this>
     */
    public function cashbackPayments(): HasMany
    {
        return $this->hasMany(CashbackPayment::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\Spatie\Permission\Models\Role, $this>
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(\Spatie\Permission\Models\Role::class);
    }

    public function getCurrentBadge(): ?Badge
    {
        return $this->badges()->orderBy('tier', 'desc')->first();
    }

    public function getTotalPointsAttribute(): int
    {
        return $this->loyaltyPoints->total_earned ?? 0;
    }

    public function getAvailablePointsAttribute(): int
    {
        return $this->loyaltyPoints->points ?? 0;
    }
}
