<?php

namespace App\Http\Middleware;

use Closure;
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class VerifyCloudflareAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->shouldVerify()) {
            return $next($request);
        }

        $teamDomain = (string) config('demo.access_team_domain');
        $audience = (string) config('demo.access_audience');
        $token = (string) $request->header('Cf-Access-Jwt-Assertion', '');

        if ($teamDomain === '' || $audience === '') {
            Log::critical('Cloudflare Access enforcement is enabled without complete configuration.');

            abort(503, 'The shared demo access gate is not configured.');
        }

        if ($token === '') {
            abort(403, 'Cloudflare Access authentication is required.');
        }

        try {
            $keys = Cache::remember(
                'cloudflare-access.jwks.'.hash('sha256', $teamDomain),
                now()->addHours(6),
                fn (): array => Http::acceptJson()
                    ->timeout(5)
                    ->retry(2, 200)
                    ->get("https://{$teamDomain}/cdn-cgi/access/certs")
                    ->throw()
                    ->json()
            );

            $claims = JWT::decode($token, JWK::parseKeySet($keys));
            $audiences = is_array($claims->aud ?? null) ? $claims->aud : [$claims->aud ?? null];
            $expectedIssuer = "https://{$teamDomain}";

            if (! in_array($audience, $audiences, true) || ($claims->iss ?? null) !== $expectedIssuer) {
                abort(403, 'The Cloudflare Access token is not valid for this application.');
            }
        } catch (HttpExceptionInterface $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            Log::warning('Cloudflare Access token verification failed.', [
                'error' => $exception->getMessage(),
                'path' => $request->path(),
            ]);

            abort(403, 'The Cloudflare Access token could not be verified.');
        }

        return $next($request);
    }

    private function shouldVerify(): bool
    {
        return (bool) config('demo.enabled')
            && config('demo.runtime') === 'server'
            && (bool) config('demo.access_enforced');
    }
}
