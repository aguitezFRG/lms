<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class SharedDemoResetController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        abort_unless(config('demo.enabled') && config('demo.runtime') === 'server', 404);

        $idempotencyKey = trim((string) $request->header('X-Demo-Reset-Key', ''));
        $signature = strtolower(trim((string) $request->header('X-Demo-Reset-Signature', '')));
        $secret = (string) config('demo.reset_hmac_secret');

        abort_if($idempotencyKey === '' || strlen($idempotencyKey) > 64, 422, 'A valid reset idempotency key is required.');
        abort_if($secret === '', 503, 'The reset endpoint is not configured.');

        $expectedSignature = hash_hmac('sha256', $idempotencyKey, $secret);
        abort_unless(hash_equals($expectedSignature, $signature), 403, 'The reset signature is invalid.');

        $exitCode = Artisan::call('demo:reset-shared', [
            '--force' => true,
            '--idempotency-key' => $idempotencyKey,
        ]);

        return response()->json([
            'ok' => $exitCode === 0,
            'idempotency_key' => $idempotencyKey,
            'message' => trim(Artisan::output()),
        ], $exitCode === 0 ? 200 : 503);
    }
}
