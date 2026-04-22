<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;

class SystemUsagePolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, [
            UserRole::SUPER_ADMIN,
            UserRole::COMMITTEE,
            UserRole::IT,
            UserRole::RR,
        ]);
    }
}
