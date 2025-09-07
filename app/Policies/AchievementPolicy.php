<?php

namespace App\Policies;

use App\Models\Achievement;
use App\Models\User;

class AchievementPolicy
{
    /**
     * Determine whether the user can list achievements.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('list achievement');
    }

    /**
     * Determine whether the user can view a specific achievement.
     */
    public function view(User $user, ?Achievement $achievement = null): bool
    {
        return $user->can('view achievement');
    }

    /**
     * Determine whether the user can create achievements.
     */
    public function create(User $user): bool
    {
        return $user->can('create achievement');
    }

    /**
     * Determine whether the user can update a specific achievement.
     */
    public function update(User $user, Achievement $achievement): bool
    {
        return $user->can('edit achievement');
    }

    /**
     * Determine whether the user can delete a specific achievement.
     */
    public function delete(User $user, Achievement $achievement): bool
    {
        return $user->can('delete achievement');
    }
}
