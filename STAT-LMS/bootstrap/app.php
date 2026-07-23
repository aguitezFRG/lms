<?php

use App\Http\Middleware\DecryptLivewirePasswords;
use App\Http\Middleware\DemoAuthenticate;
use App\Http\Middleware\EnsureProfileComplete;
use App\Http\Middleware\SetSecurityHeaders;
use App\Http\Middleware\TrackRequestTiming;
use App\Http\Middleware\VerifyCloudflareAccess;
use Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $trustedProxies = array_values(array_filter(array_map(
            static fn (string $proxy): string => trim($proxy),
            explode(',', (string) env('TRUSTED_PROXIES', '127.0.0.1,::1'))
        )));

        $middleware->trustProxies(
            at: $trustedProxies === ['*'] ? '*' : $trustedProxies,
            headers: Request::HEADER_X_FORWARDED_FOR |
                 Request::HEADER_X_FORWARDED_HOST |
                 Request::HEADER_X_FORWARDED_PORT |
                 Request::HEADER_X_FORWARDED_PROTO
        );

        // Decrypt RSA-encrypted password fields from Livewire update payloads
        $middleware->append(SetSecurityHeaders::class);
        $middleware->append(TrackRequestTiming::class);

        $middleware->web(
            prepend: [
                VerifyCloudflareAccess::class,
            ],
            append: [
                DecryptLivewirePasswords::class,
                DemoAuthenticate::class,
            ],
        );
        $middleware->prependToPriorityList(
            AuthenticatesRequests::class,
            DemoAuthenticate::class,
        );
        $middleware->redirectGuestsTo('/login');

        $middleware->alias([
            'profile.complete' => EnsureProfileComplete::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
