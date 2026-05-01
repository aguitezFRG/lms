<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfBanned
{
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check() && auth()->user()->is_banned) {
            $loginUrl = url('/app/login');

            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            $request->session()->put('error', 'Your account is banned from accessing the system. Please contact the administrator.');

            return redirect($loginUrl);
        }

        return $next($request);
    }
}
