<?php

namespace App\Http\Middleware;

use App\Services\PasswordEncryptionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Decrypts password fields that the browser encrypted with RSA-OAEP before
 * sending them through Livewire. Values are prefixed with "ENC:" by the
 * client-side fetch interceptor.
 *
 * Only touches the `updates` map in Livewire's JSON payload — method-call
 * params (used by the profile change-password modal) are left untouched
 * because that flow handles its own decryption.
 */
class DecryptLivewirePasswords
{
    public function handle(Request $request, Closure $next): Response
    {
        if (preg_match('#/livewire[^/]*/update#', $request->getPathInfo()) && $request->isJson()) {
            $payload = $request->json()->all();
            $modified = false;

            if (isset($payload['components']) && is_array($payload['components'])) {
                $service = app(PasswordEncryptionService::class);

                foreach ($payload['components'] as &$component) {
                    if (! isset($component['updates']) || ! is_array($component['updates'])) {
                        continue;
                    }

                    foreach ($component['updates'] as $key => &$value) {
                        if (
                            is_string($value)
                            && str_starts_with($value, 'ENC:')
                            && $this->isPasswordKey($key)
                        ) {
                            try {
                                $value = $service->decrypt(substr($value, 4));
                                $modified = true;
                            } catch (\Throwable) {
                                // If decryption fails, leave the value as-is;
                                // server-side validation will reject it.
                            }
                        }
                    }
                    unset($value);
                }
                unset($component);
            }

            // Only replace the ParameterBag when something was actually decrypted,
            // avoiding unnecessary JSON serialisation on every Livewire poll.
            if ($modified) {
                $request->json()->replace($payload);
            }
        }

        return $next($request);
    }

    /** Keys that end with "password" (e.g. data.password, data.current_password). */
    private function isPasswordKey(string $key): bool
    {
        return (bool) preg_match('/password$/i', $key);
    }
}
