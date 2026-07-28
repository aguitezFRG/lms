<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Support\RoleViewMode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RoleViewModeController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        abort_unless(RoleViewMode::canUse($request->user()), 403);

        $validated = $request->validate([
            'role' => ['nullable', 'string', Rule::in([...RoleViewMode::previewRoleValues(), '', 'actual'])],
        ]);

        $role = $validated['role'] ?? null;

        if ($role === null || $role === '' || $role === 'actual') {
            RoleViewMode::clear();
        } else {
            RoleViewMode::set(UserRole::from($role));
        }

        $target = match (true) {
            in_array($role, ['student', 'faculty']) => '/admin/user-onboarding',
            default => '/admin/admin-onboarding',
        };

        return redirect($target);
    }
}
