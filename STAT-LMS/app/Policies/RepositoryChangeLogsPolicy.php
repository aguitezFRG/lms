<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\RepositoryChangeLogs;
use App\Models\User;

class RepositoryChangeLogsPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, [UserRole::SUPER_ADMIN, UserRole::COMMITTEE, UserRole::IT]);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, RepositoryChangeLogs $repositoryChangeLogs): bool
    {
        return in_array($user->role, [UserRole::SUPER_ADMIN, UserRole::COMMITTEE, UserRole::IT]);
    }

    /**
     * Determine whether the user can create models.
     * Audit logs are created only by the observer — never by user action.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the model.
     * Audit logs are immutable — no role may edit them.
     */
    public function update(User $user, RepositoryChangeLogs $repositoryChangeLogs): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, RepositoryChangeLogs $repositoryChangeLogs): bool
    {
        return false;
    }

    public function restoreAny(User $user): bool
    {
        return in_array($user->role, [UserRole::SUPER_ADMIN, UserRole::COMMITTEE, UserRole::IT]);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, RepositoryChangeLogs $repositoryChangeLogs): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, RepositoryChangeLogs $repositoryChangeLogs): bool
    {
        return false;
    }
}
