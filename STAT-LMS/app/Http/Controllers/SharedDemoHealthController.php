<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Artisan;

class SharedDemoHealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        abort_unless(config('demo.enabled') && config('demo.runtime') === 'server', 404);

        $exitCode = Artisan::call('demo:health-shared', ['--json' => true]);
        $payload = json_decode(trim(Artisan::output()), true);

        if (! is_array($payload)) {
            $payload = [
                'healthy' => false,
                'error' => 'The readiness command returned an invalid response.',
            ];
        }

        return response()->json($payload, $exitCode === 0 ? 200 : 503);
    }
}
