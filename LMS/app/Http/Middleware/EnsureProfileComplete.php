<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureProfileComplete
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user && ! $user->is_profile_complete) {
            if (! $request->routeIs('filament.user.pages.onboarding', 'auth.google.*')) {
                return redirect('/app/onboarding');
            }
        }

        return $next($request);
    }
}
