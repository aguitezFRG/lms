<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;
use App\Support\RoleViewMode;

class SystemUsagePolicy
{
    public function viewAny(User $user): bool
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
}
