<?php

namespace App\Policies;

use App\Models\RrMaterials;
use App\Models\User;
use Illuminate\Auth\Access\Response;

use App\Enums\UserRole;

class RrMaterialsPolicy
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
    public function view(User $user, RrMaterials $rrMaterials): bool
    {
        $user_access_level = UserRole::from($user->role)->getAccessLevel();
        $RR_access_level = $rrMaterials->parent->access_level ?? 1;
        return $user_access_level >= $RR_access_level;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return in_array($user->role, [UserRole::COMMITTEE->value, UserRole::IT->value, UserRole::RR->value, UserRole::FACULTY->value]);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, RrMaterials $rrMaterials): bool
    {
        return $user->id === $rrMaterials->created_by || in_array($user->role, [UserRole::COMMITTEE->value, UserRole::IT->value, UserRole::RR->value, UserRole::FACULTY->value]);
    }

    public function deleteAny(User $user): bool
    {
        return in_array($user->role, [UserRole::COMMITTEE->value, UserRole::IT->value]);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, RrMaterials $rrMaterials): bool
    {
        return $user->id === $rrMaterials->created_by || in_array($user->role, [UserRole::COMMITTEE->value, UserRole::IT->value]);
    }

    public function restoreAny(User $user): bool
    {
        return in_array($user->role, [UserRole::COMMITTEE->value, UserRole::IT->value]);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, RrMaterials $rrMaterials): bool
    {
        return $user->id === $rrMaterials->created_by || in_array($user->role, [UserRole::COMMITTEE->value, UserRole::IT->value]);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, RrMaterials $rrMaterials): bool
    {
        return in_array($user->role, [UserRole::COMMITTEE->value, UserRole::IT->value]);
    }
}
