<?php

namespace App\Services;

use RuntimeException;

class PasswordEncryptionService
{
    private string $privatePath;
    private string $publicPath;

    public function __construct()
    {
        $this->privatePath = storage_path('app/keys/password_private.pem');
        $this->publicPath  = storage_path('app/keys/password_public.pem');
    }

    /** Returns the PEM-encoded public key for the client to use. */
    public function publicKeyPem(): string
    {
        if (! file_exists($this->publicPath)) {
            throw new RuntimeException(
                'Password encryption keys not found. Run: php artisan app:generate-password-keys'
            );
        }

        return file_get_contents($this->publicPath);
    }

    /**
     * Decrypt a Base64-encoded RSA-OAEP ciphertext (produced by the browser's
     * SubtleCrypto API) back to the original plaintext password.
     */
    public function decrypt(string $base64Ciphertext): string
    {
        if (! file_exists($this->privatePath)) {
            throw new RuntimeException(
                'Password encryption keys not found. Run: php artisan app:generate-password-keys'
            );
        }

        $privateKey = openssl_pkey_get_private(file_get_contents($this->privatePath));

        if (! $privateKey) {
            throw new RuntimeException('Failed to load private key.');
        }

        $ciphertext = base64_decode($base64Ciphertext, strict: true);

        if ($ciphertext === false) {
            throw new RuntimeException('Invalid base64 ciphertext.');
        }

        $plaintext = '';
        $ok = openssl_private_decrypt(
            $ciphertext,
            $plaintext,
            $privateKey,
            OPENSSL_PKCS1_OAEP_PADDING
        );

        if (! $ok) {
            throw new RuntimeException('Decryption failed.');
        }

        return $plaintext;
    }
}
