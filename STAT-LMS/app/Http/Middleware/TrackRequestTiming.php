<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class TrackRequestTiming
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $enabled = (bool) config('performance.request_timing.enabled', false);
        $paths = config('performance.request_timing.paths', ['/app/login', '/admin/login', '/app/user-onboarding']);

        if (! $enabled || ! $this->isTrackedPath($request, $paths)) {
            return $next($request);
        }

        $queryCount = 0;
        $queryTimeMs = 0.0;
        $querySamples = [];

        DB::listen(function (QueryExecuted $query) use (&$queryCount, &$queryTimeMs, &$querySamples): void {
            $queryCount++;
            $queryTimeMs += (float) $query->time;

            // Keep a compact query sample to diagnose route-local latency hotspots.
            $querySamples[] = [
                'time_ms' => round((float) $query->time, 2),
                'sql' => $query->sql,
            ];
        });

        $startedAt = hrtime(true);
        $response = $next($request);
        $totalMs = (hrtime(true) - $startedAt) / 1_000_000;
        $appMs = max(0, $totalMs - $queryTimeMs);

        $response->headers->set('X-Request-Time-Ms', number_format($totalMs, 2, '.', ''));
        $response->headers->set('X-DB-Time-Ms', number_format($queryTimeMs, 2, '.', ''));
        $response->headers->set('X-DB-Query-Count', (string) $queryCount);

        Log::channel('request_timing')->info('request_timing', [
            'method' => $request->method(),
            'path' => '/'.$request->path(),
            'full_url' => $request->fullUrl(),
            'route' => optional($request->route())->getName(),
            'status' => $response->getStatusCode(),
            'total_ms' => round($totalMs, 2),
            'app_ms' => round($appMs, 2),
            'db_ms' => round($queryTimeMs, 2),
            'db_query_count' => $queryCount,
            'db_top_queries' => $this->topQueries($querySamples),
            'memory_peak_mb' => round(memory_get_peak_usage(true) / 1048576, 2),
            'user_id' => optional($request->user())->id,
            'cf_ray' => $request->header('cf-ray'),
            'x_request_id' => $request->header('x-request-id'),
        ]);

        return $response;
    }

    /**
     * Check whether the current request path should be logged.
     */
    private function isTrackedPath(Request $request, array $paths): bool
    {
        $normalizedPath = '/'.ltrim($request->path(), '/');

        foreach ($paths as $path) {
            $normalizedTrackedPath = '/'.ltrim($path, '/');
            if ($normalizedPath === $normalizedTrackedPath) {
                return true;
            }
        }

        return false;
    }

    /**
     * Keep only top N slowest SQL statements for compact log output.
     */
    private function topQueries(array $querySamples, int $limit = 5): array
    {
        usort($querySamples, static function (array $a, array $b): int {
            return $b['time_ms'] <=> $a['time_ms'];
        });

        return array_slice($querySamples, 0, $limit);
    }
}
