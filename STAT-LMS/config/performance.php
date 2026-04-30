<?php

return [
    'request_timing' => [
        'enabled' => (bool) env('REQUEST_TIMING_ENABLED', false),
        'paths' => array_values(array_filter(array_map(
            static fn (string $path): string => trim($path),
            explode(',', (string) env('REQUEST_TIMING_PATHS', '/app/login,/admin/login,/app/user-onboarding'))
        ))),
    ],
];
