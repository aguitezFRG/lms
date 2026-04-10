<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GeneratePasswordEncryptionKeys extends Command
{
    protected $signature   = 'app:generate-password-keys {--force : Overwrite existing keys}';
    protected $description = 'Generate RSA key pair for client-side password encryption';

    public function handle(): int
    {
        $dir        = storage_path('app/keys');
        $privatePath = "$dir/password_private.pem";
        $publicPath  = "$dir/password_public.pem";

        if (! is_dir($dir)) {
            mkdir($dir, 0700, true);
        }

        if (file_exists($privatePath) && ! $this->option('force')) {
            $this->warn('Keys already exist. Use --force to regenerate.');
            return self::FAILURE;
        }

        $key = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
            'config'           => $this->opensslConfig(),
        ]);

        if ($key === false) {
            $this->error('openssl_pkey_new() failed: ' . (openssl_error_string() ?: 'unknown error'));
            return self::FAILURE;
        }

        openssl_pkey_export($key, $privateKey, null, ['config' => $this->opensslConfig()]);
        $publicKey = openssl_pkey_get_details($key)['key'];

        file_put_contents($privatePath, $privateKey);
        chmod($privatePath, 0600);

        file_put_contents($publicPath, $publicKey);
        chmod($publicPath, 0644);

        $this->info("Keys written to storage/app/keys/");
        $this->line("  Private: $privatePath");
        $this->line("  Public:  $publicPath");

        return self::SUCCESS;
    }

    private function opensslConfig(): string
    {
        foreach (['/etc/ssl/openssl.cnf', '/usr/lib/ssl/openssl.cnf', '/usr/local/etc/openssl/openssl.cnf'] as $path) {
            if (file_exists($path)) return $path;
        }
        return '/etc/ssl/openssl.cnf'; // best guess
    }
}
