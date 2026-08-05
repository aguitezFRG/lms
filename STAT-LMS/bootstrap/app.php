<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;

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

        $middleware->append(\App\Http\Middleware\SetSecurityHeaders::class);
        $middleware->append(\App\Http\Middleware\TrackRequestTiming::class);

        $middleware->web(
            append: [
                \App\Http\Middleware\DecryptLivewirePasswords::class,
                \App\Http\Middleware\DemoAuthenticate::class,
            ],
        );
        $middleware->prependToPriorityList(
            \Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests::class,
            \App\Http\Middleware\DemoAuthenticate::class,
        );
        $middleware->redirectGuestsTo('/login');

        $middleware->alias([
            'profile.complete' => \App\Http\Middleware\EnsureProfileComplete::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Throwable $exception, Request $request) {
            $current = $exception;
            $messages = [];
            $hasDatabaseException = false;
            $sqlState = null;

            do {
                $messages[] = strtolower($current->getMessage());

                if ($current instanceof QueryException || $current instanceof \PDOException) {
                    $hasDatabaseException = true;
                }

                if ($current instanceof \PDOException && is_string($current->getCode())) {
                    $sqlState ??= strtoupper($current->getCode());
                }

                $current = $current->getPrevious();
            } while ($current !== null);

            if (! $hasDatabaseException) {
                return null;
            }

            $message = implode(' ', $messages);
            $isConnectionSqlState = false;

            if ($sqlState !== null) {
                foreach (['08', '53', '57P01', '57P02', '57P03'] as $state) {
                    if (str_starts_with($sqlState, $state)) {
                        $isConnectionSqlState = true;
                        break;
                    }
                }
            }

            $isConnectionMessage = str_contains($message, 'connection refused')
                || str_contains($message, 'could not connect to server')
                || str_contains($message, 'server closed the connection unexpectedly')
                || str_contains($message, 'connection timed out')
                || str_contains($message, 'could not translate host name')
                || str_contains($message, 'temporary failure in name resolution')
                || str_contains($message, 'network is unreachable')
                || str_contains($message, 'no route to host')
                || str_contains($message, 'remaining connection slots are reserved')
                || str_contains($message, 'too many connections');

            if (! $isConnectionSqlState && ! $isConnectionMessage) {
                return null;
            }

            $headers = ['Retry-After' => '60'];

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'The database service is temporarily unavailable. Please try again shortly.',
                ], 503, $headers);
            }

            return response()->view('errors.503', [], 503, $headers);
        });
    })->create();
