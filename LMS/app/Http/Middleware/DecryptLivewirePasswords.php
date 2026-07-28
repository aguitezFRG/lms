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
 * Touches both the `updates` map and `calls[*].params` in Livewire's JSON payload.
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
                    if (isset($component['updates']) && is_array($component['updates'])) {
                        if ($this->decryptInArray($component['updates'], $service)) {
                            $modified = true;
                        }
                    }

                    if (isset($component['calls']) && is_array($component['calls'])) {
                        foreach ($component['calls'] as &$call) {
                            if (isset($call['params']) && is_array($call['params'])) {
                                if ($this->decryptInArray($call['params'], $service)) {
                                    $modified = true;
                                }
                            }
                        }
                        unset($call);
                    }
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

    /**
     * Recursively scan an array and decrypt ENC:-prefixed password values.
     * Returns true if any decryption happened.
     */
    private function decryptInArray(array &$target, PasswordEncryptionService $service): bool
    {
        $modified = false;

        foreach ($target as $key => &$value) {
            if (is_array($value)) {
                if ($this->decryptInArray($value, $service)) {
                    $modified = true;
                }
            } elseif (
                is_string($value)
                && str_starts_with($value, 'ENC:')
                && $this->isPasswordKey((string) $key)
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

        return $modified;
    }

    /** Keys that end with "password" (e.g. data.password, data.current_password). */
    private function isPasswordKey(string $key): bool
    {
        return (bool) preg_match('/password$/i', $key);
    }
}
