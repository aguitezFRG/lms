<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TurnstileService
{
    private const VERIFY_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    public function verify(string $token, ?string $ipAddress): bool
    {
        $secretKey = (string) config('services.turnstile.secret_key');
        $expectedAction = (string) config('services.turnstile.action');
        $expectedHostname = (string) config('services.turnstile.hostname');

        if ($secretKey === '') {
            Log::critical('Turnstile enforcement is enabled without a secret key.');

            return false;
        }

        try {
            $response = Http::asForm()
                ->acceptJson()
                ->timeout(5)
                ->retry(2, 200, throw: false)
                ->post(self::VERIFY_URL, array_filter([
                    'secret' => $secretKey,
                    'response' => $token,
                    'remoteip' => $ipAddress,
                    'idempotency_key' => (string) Str::uuid(),
                ], static fn (mixed $value): bool => $value !== null && $value !== ''));

            $result = $response->json();

            $verified = $response->successful()
                && is_array($result)
                && ($result['success'] ?? false) === true
                && ($expectedAction === '' || ($result['action'] ?? null) === $expectedAction)
                && ($expectedHostname === '' || ($result['hostname'] ?? null) === $expectedHostname);

            if (! $verified) {
                Log::notice('Turnstile verification rejected.', [
                    'status' => $response->status(),
                    'error_codes' => is_array($result) ? ($result['error-codes'] ?? []) : [],
                    'action_matches' => $expectedAction === '' || ($result['action'] ?? null) === $expectedAction,
                    'hostname_matches' => $expectedHostname === '' || ($result['hostname'] ?? null) === $expectedHostname,
                ]);
            }

            return $verified;
        } catch (\Throwable $exception) {
            Log::warning('Turnstile verification request failed.', [
                'error' => $exception->getMessage(),
            ]);

            return false;
        }
    }
}
