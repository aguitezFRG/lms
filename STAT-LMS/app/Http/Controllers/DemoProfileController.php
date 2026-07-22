<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\User;
use App\Support\RoleViewMode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DemoProfileController extends Controller
{
    public function index(): View
    {
        abort_unless(config('demo.enabled'), 404);

        $selectedProfileId = session(config('demo.profile_session_key'));

        $profiles = User::query()
            ->where('is_banned', false)
            ->orderByRaw("case role
                when 'student' then 1
                when 'faculty' then 2
                when 'staff/custodian' then 3
                when 'committee' then 4
                when 'it' then 5
                when 'super_admin' then 6
                else 7 end")
            ->orderBy('name')
            ->get();

        return view('demo.profiles', compact('profiles', 'selectedProfileId'));
    }

    public function select(Request $request): RedirectResponse
    {
        abort_unless(config('demo.enabled'), 404);

        $validated = $request->validate([
            'profile_id' => ['required', 'uuid'],
        ]);

        $user = User::query()
            ->whereKey($validated['profile_id'])
            ->where('is_banned', false)
            ->firstOrFail();

        $request->session()->regenerate();
        RoleViewMode::clear();
        $request->session()->put(config('demo.profile_session_key'), $user->getKey());

        return redirect()->to($this->panelPath($user->role));
    }

    private function panelPath(UserRole $role): string
    {
        return in_array($role, [UserRole::SUPER_ADMIN, UserRole::COMMITTEE, UserRole::IT, UserRole::RR], true)
            ? '/admin'
            : '/app';
    }
}
