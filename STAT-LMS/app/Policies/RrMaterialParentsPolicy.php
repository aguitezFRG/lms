<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\RrMaterialParents;
use App\Models\User;
use App\Support\RoleViewMode;

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

        $user_access_level = RoleViewMode::effectiveAccessLevel($user);

        return $user_access_level >= $rrMaterialParents->access_level;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        if (RoleViewMode::isPreviewingLowerRole($user)) {
            return false;
        }

        return in_array($user->role, [UserRole::SUPER_ADMIN, UserRole::COMMITTEE, UserRole::IT]);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, RrMaterialParents $rrMaterialParents): bool
    {
        if (RoleViewMode::isPreviewingLowerRole($user)) {
            return false;
        }

        return in_array($user->role, [UserRole::SUPER_ADMIN, UserRole::COMMITTEE, UserRole::IT]);
    }

    public function deleteAny(User $user): bool
    {
        if (RoleViewMode::isPreviewingLowerRole($user)) {
            return false;
        }

        return in_array($user->role, [UserRole::SUPER_ADMIN, UserRole::COMMITTEE, UserRole::IT]);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, RrMaterialParents $rrMaterialParents): bool
    {
        if (RoleViewMode::isPreviewingLowerRole($user)) {
            return false;
        }

        return in_array($user->role, [UserRole::SUPER_ADMIN, UserRole::COMMITTEE, UserRole::IT]);
    }

    public function restoreAny(User $user): bool
    {
        if (RoleViewMode::isPreviewingLowerRole($user)) {
            return false;
        }

        return in_array($user->role, [UserRole::SUPER_ADMIN, UserRole::COMMITTEE, UserRole::IT]);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, RrMaterialParents $rrMaterialParents): bool
    {
        if (RoleViewMode::isPreviewingLowerRole($user)) {
            return false;
        }

        return in_array($user->role, [UserRole::SUPER_ADMIN, UserRole::COMMITTEE, UserRole::IT]);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, RrMaterialParents $rrMaterialParents): bool
    {
        if (RoleViewMode::isPreviewingLowerRole($user)) {
            return false;
        }

        return in_array($user->role, [UserRole::SUPER_ADMIN, UserRole::COMMITTEE, UserRole::IT]);
    }
}
