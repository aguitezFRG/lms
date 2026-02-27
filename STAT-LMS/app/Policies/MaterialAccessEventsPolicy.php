<?php

namespace App\Policies;

use App\Models\MaterialAccessEvents;
use App\Models\User;
use Illuminate\Auth\Access\Response;

use App\Enums\UserRole;

class MaterialAccessEventsPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, [UserRole::COMMITTEE->value, UserRole::IT->value]);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, MaterialAccessEvents $materialAccessEvents): bool
    {
        return in_array($user->role, [UserRole::COMMITTEE->value, UserRole::IT->value]);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return in_array($user->role, [UserRole::COMMITTEE->value, UserRole::IT->value]);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, MaterialAccessEvents $materialAccessEvents): bool
    {
        return in_array($user->role, [UserRole::COMMITTEE->value, UserRole::IT->value]);
    }

    public function deleteAny(User $user): bool
    {
        return in_array($user->role, [UserRole::COMMITTEE->value, UserRole::IT->value]);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, MaterialAccessEvents $materialAccessEvents): bool
    {
        return in_array($user->role, [UserRole::COMMITTEE->value, UserRole::IT->value]);
    }

    public function restoreAny(User $user): bool
    {
        return in_array($user->role, [UserRole::COMMITTEE->value, UserRole::IT->value]);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, MaterialAccessEvents $materialAccessEvents): bool
    {
        return in_array($user->role, [UserRole::COMMITTEE->value, UserRole::IT->value]);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, MaterialAccessEvents $materialAccessEvents): bool
    {
        return false; // No one can permanently delete material access events
    }
}
