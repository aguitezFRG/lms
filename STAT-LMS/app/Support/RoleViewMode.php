<?php

namespace App\Support;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class RoleViewMode
{
    public const SESSION_KEY = 'admin_role_view_mode';

    /**
     * @return array<int, UserRole>
     */
    public static function allowedOperators(): array
    {
        return [
            UserRole::SUPER_ADMIN,
            UserRole::COMMITTEE,
        ];
    }

    /**
     * @return array<int, UserRole>
     */
    public static function previewRoles(): array
    {
        return [
            UserRole::STUDENT,
            UserRole::FACULTY,
            UserRole::RR,
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function previewRoleValues(): array
    {
        return array_map(
            fn (UserRole $role): string => $role->value,
            self::previewRoles(),
        );
    }

    public static function canUse(?User $user = null): bool
    {
        $user ??= Auth::user();

        return $user instanceof User
            && in_array($user->role, self::allowedOperators(), true);
    }

    public static function selectedRole(): ?UserRole
    {
        $value = Session::get(self::SESSION_KEY);

        if (! is_string($value)) {
            return null;
        }

        $role = UserRole::tryFrom($value);

        return $role && in_array($role, self::previewRoles(), true) ? $role : null;
    }

    public static function effectiveRole(?User $user = null): ?UserRole
    {
        $user ??= Auth::user();

        if (! $user instanceof User) {
            return null;
        }

        if (! self::canUse($user)) {
            return $user->role;
        }

        return self::selectedRole() ?? $user->role;
    }

    public static function effectiveAccessLevel(?User $user = null): int
    {
        return self::effectiveRole($user)?->getAccessLevel() ?? 1;
    }

    public static function isPreviewing(?User $user = null): bool
    {
        return self::canUse($user) && self::selectedRole() !== null;
    }

    public static function isPreviewingLowerRole(?User $user = null): bool
    {
        $user ??= Auth::user();

        return self::isPreviewing($user)
            && in_array(self::selectedRole(), self::previewRoles(), true);
    }

    public static function isUserRolePreview(?User $user = null): bool
    {
        $user ??= Auth::user();

        return self::isPreviewing($user)
            && in_array(self::selectedRole(), [UserRole::STUDENT, UserRole::FACULTY], true);
    }

    public static function isRrPreview(?User $user = null): bool
    {
        $user ??= Auth::user();

        return self::isPreviewing($user) && self::selectedRole() === UserRole::RR;
    }

    public static function set(UserRole $role): void
    {
        if (! in_array($role, self::previewRoles(), true)) {
            self::clear();

            return;
        }

        Session::put(self::SESSION_KEY, $role->value);
    }

    public static function clear(): void
    {
        Session::forget(self::SESSION_KEY);
    }
}
