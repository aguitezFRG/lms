<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

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
            headers: \Illuminate\Http\Request::HEADER_X_FORWARDED_FOR |
                 \Illuminate\Http\Request::HEADER_X_FORWARDED_HOST |
                 \Illuminate\Http\Request::HEADER_X_FORWARDED_PORT |
                 \Illuminate\Http\Request::HEADER_X_FORWARDED_PROTO
        );

        // Decrypt RSA-encrypted password fields from Livewire update payloads
        $middleware->append(\App\Http\Middleware\SetSecurityHeaders::class);

        $middleware->web(append: [
            \App\Http\Middleware\DecryptLivewirePasswords::class,
        ]);

        $middleware->redirectGuestsTo('/app/login');

        $middleware->alias([
            'profile.complete' => \App\Http\Middleware\EnsureProfileComplete::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
