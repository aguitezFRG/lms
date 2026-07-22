<?php

namespace App\Http\Middleware;

use App\Models\User;
use Filament\Facades\Filament;
use Filament\Http\Middleware\Authenticate;

class FilamentAuthenticate extends Authenticate
{
    protected function authenticate($request, array $guards): void
    {
        if (config('demo.enabled') && ! Filament::auth()->check()) {
            $userId = $request->session()->get(config('demo.profile_session_key'));

            if (is_string($userId)) {
                $user = User::query()
                    ->whereKey($userId)
                    ->where('is_banned', false)
                    ->first();

                if ($user !== null) {
                    Filament::auth()->setUser($user);
                } else {
                    $request->session()->forget(config('demo.profile_session_key'));
                }
            }
        }

        parent::authenticate($request, $guards);
    }

    protected function redirectTo($request): ?string
    {
        if (config('demo.enabled')) {
            return route('demo.profiles.index');
        }

        return parent::redirectTo($request);
    }
}
