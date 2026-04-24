<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GoogleController extends Controller
{
    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Throwable $e) {
            Log::error('Google OAuth callback failed', ['exception' => $e->getMessage()]);

            return redirect('/app/login')->with('error', 'Google sign-in failed. Please try again.');
        }

        // 1. Fast path: repeat login by google_id
        $user = User::where('google_id', $googleUser->getId())->first();

        // 2. Account linking: email match (withTrashed to avoid unique constraint crash on soft-deleted accounts)
        if (! $user) {
            $user = User::withTrashed()->where('email', $googleUser->getEmail())->first();
            if ($user && ! $user->trashed()) {
                $user->google_id = $googleUser->getId();
                if (! $user->email_verified_at) {
                    $user->email_verified_at = now();
                }
                $user->saveQuietly();
            }
        }

        // 3. Deny soft-deleted accounts before attempting creation
        if ($user && $user->trashed()) {
            return redirect('/app/login')->with('error', 'This account has been deactivated.');
        }

        // 4. Check for soft-deleted google_id collision before creating
        if (! $user) {
            if (User::withTrashed()->where('google_id', $googleUser->getId())->exists()) {
                return redirect('/app/login')->with('error', 'This account has been deactivated.');
            }
        }

        // 5. New user — always student with incomplete profile
        if (! $user) {
            $user = User::create([
                'name' => $googleUser->getName(),
                'f_name' => $googleUser->user['given_name'] ?? null,
                'l_name' => $googleUser->user['family_name'] ?? null,
                'email' => $googleUser->getEmail(),
                'google_id' => $googleUser->getId(),
                'password' => bcrypt(Str::random(40)),
                'role' => UserRole::STUDENT,
                'is_profile_complete' => false,
                'email_verified_at' => now(),
            ]);
        }

        Auth::login($user, remember: true);

        // 5. Route based on state
        if (! $user->is_profile_complete) {
            return redirect()->route('filament.user.pages.onboarding');
        }

        $adminRoles = [UserRole::SUPER_ADMIN, UserRole::COMMITTEE, UserRole::IT, UserRole::RR];

        return in_array($user->role, $adminRoles)
            ? redirect('/admin')
            : redirect('/app');
    }
}
