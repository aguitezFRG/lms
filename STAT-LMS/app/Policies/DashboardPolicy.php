<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;

class DashboardPolicy
{
    public function viewGeneral(User $user): bool
    {
        return in_array($user->role, [
            UserRole::SUPER_ADMIN->value,
            UserRole::COMMITTEE->value,
            UserRole::IT->value,
            UserRole::RR->value,
        ]);
    }

    public function viewBorrows(User $user): bool
    {
        return in_array($user->role, [
            UserRole::SUPER_ADMIN->value,
            UserRole::COMMITTEE->value,
            UserRole::IT->value,
            UserRole::RR->value,
        ]);
    }

    public function viewAccess(User $user): bool
    {
        return in_array($user->role, [
            UserRole::SUPER_ADMIN->value,
            UserRole::COMMITTEE->value,
            UserRole::IT->value,
        ]);
    }
}
