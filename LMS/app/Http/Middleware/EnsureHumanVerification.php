<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureHumanVerification
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->shouldEnforce($request) || $this->hasValidVerification($request)) {
            return $next($request);
        }

        if ($request->isMethod('GET') || $request->isMethod('HEAD')) {
            if ($request->isMethod('GET')) {
                $request->session()->put('turnstile.intended', $request->fullUrl());
            }

            return redirect()->route('turnstile.show');
        }

        abort(403, 'Human verification is required.');
    }

    private function shouldEnforce(Request $request): bool
    {
        if (
            ! (bool) config('services.turnstile.enabled')
            || ! (bool) config('demo.enabled')
            || config('demo.runtime') !== 'server'
        ) {
            return false;
        }

        return ! $request->routeIs(
            'turnstile.*',
            'shared-demo.health',
            'shared-demo.reset',
        );
    }

    private function hasValidVerification(Request $request): bool
    {
        $verifiedAt = $request->session()->get((string) config('services.turnstile.session_key'));

        if (! is_int($verifiedAt) && ! ctype_digit((string) $verifiedAt)) {
            return false;
        }

        $lifetime = max(60, (int) config('services.turnstile.session_lifetime'));

        return (int) $verifiedAt >= now()->subSeconds($lifetime)->timestamp;
    }
}
