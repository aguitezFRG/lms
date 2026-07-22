<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class DemoAuthenticate
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('demo.enabled') || Auth::check()) {
            return $next($request);
        }

        $userId = $request->session()->get(config('demo.profile_session_key'));

        if (is_string($userId)) {
            $user = User::query()
                ->whereKey($userId)
                ->where('is_banned', false)
                ->first();

            if ($user !== null) {
                Auth::setUser($user);
            } else {
                $request->session()->forget(config('demo.profile_session_key'));
            }
        }

        return $next($request);
    }
}
