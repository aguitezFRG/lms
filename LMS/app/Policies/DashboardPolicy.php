<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;
use App\Support\RoleViewMode;

class DashboardPolicy
{
    public function viewGeneral(User $user): bool
    {
        if (RoleViewMode::isUserRolePreview($user)) {
            return false;
        }

        return in_array(RoleViewMode::effectiveRole($user), [
            UserRole::SUPER_ADMIN,
            UserRole::COMMITTEE,
            UserRole::IT,
            UserRole::RR,
        ]);
    }

    public function viewBorrows(User $user): bool
    {
        if (RoleViewMode::isUserRolePreview($user)) {
            return false;
        }

        return in_array(RoleViewMode::effectiveRole($user), [
            UserRole::SUPER_ADMIN,
            UserRole::COMMITTEE,
            UserRole::IT,
            UserRole::RR,
        ]);
    }

    public function viewAccess(User $user): bool
    {
        if (RoleViewMode::isPreviewingLowerRole($user)) {
            return false;
        }

        return in_array($user->role, [
            UserRole::SUPER_ADMIN,
            UserRole::COMMITTEE,
            UserRole::IT,
        ]);
    }
}
