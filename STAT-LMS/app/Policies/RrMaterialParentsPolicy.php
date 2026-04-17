<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\RrMaterialParents;
use App\Models\User;

class RrMaterialParentsPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, RrMaterialParents $rrMaterialParents): bool
    {

        $user_access_level = UserRole::from($user->role)->getAccessLevel();

        return $user_access_level >= $rrMaterialParents->access_level || $user->id === $rrMaterialParents->user_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->role !== UserRole::STUDENT->value;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, RrMaterialParents $rrMaterialParents): bool
    {
        return in_array($user->role, [UserRole::SUPER_ADMIN->value, UserRole::COMMITTEE->value, UserRole::IT->value]) || $user->id === $rrMaterialParents->user_id;
    }

    public function deleteAny(User $user): bool
    {
        return in_array($user->role, [UserRole::SUPER_ADMIN->value, UserRole::COMMITTEE->value, UserRole::IT->value]);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, RrMaterialParents $rrMaterialParents): bool
    {
        return in_array($user->role, [UserRole::SUPER_ADMIN->value, UserRole::COMMITTEE->value, UserRole::IT->value]) || $user->id === $rrMaterialParents->user_id;
    }

    public function restoreAny(User $user): bool
    {
        return in_array($user->role, [UserRole::SUPER_ADMIN->value, UserRole::COMMITTEE->value, UserRole::IT->value]);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, RrMaterialParents $rrMaterialParents): bool
    {
        return in_array($user->role, [UserRole::SUPER_ADMIN->value, UserRole::COMMITTEE->value, UserRole::IT->value]) || $user->id === $rrMaterialParents->user_id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, RrMaterialParents $rrMaterialParents): bool
    {
        return in_array($user->role, [UserRole::SUPER_ADMIN->value, UserRole::COMMITTEE->value, UserRole::IT->value]);
    }
}
