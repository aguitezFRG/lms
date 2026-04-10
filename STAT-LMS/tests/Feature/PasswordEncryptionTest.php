<?php

namespace Tests\Feature;

use App\Http\Middleware\DecryptLivewirePasswords;
use App\Services\PasswordEncryptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Feature: Client-Side RSA Password Encryption
 *
 * Covers:
 * - PasswordEncryptionService: decrypt round-trip, rejects bad input
 * - DecryptLivewirePasswords middleware: decrypts ENC:-prefixed password fields,
 *   ignores non-password keys, ignores non-Livewire routes, survives bad ciphertext
 * - AdminProfile::submitEncryptedPasswordChange: success, wrong password, bad ciphertext
 * - UserProfile::submitEncryptedPasswordChange: same three scenarios
 */
class PasswordEncryptionTest extends TestCase
{
    use RefreshDatabase;

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────

    /** Encrypt plaintext with the real public key (mirrors what the browser does). */
    private function encryptWithPublicKey(string $plaintext): string
    {
        $publicKeyPath = storage_path('app/keys/password_public.pem');

        if (! file_exists($publicKeyPath)) {
            $this->markTestSkipped('RSA keys not generated. Run: php artisan app:generate-password-keys');
        }

        $pubKey = openssl_pkey_get_public(file_get_contents($publicKeyPath));
        $this->assertNotFalse($pubKey, 'Failed to load public key');

        $encrypted = '';
        $ok = openssl_public_encrypt($plaintext, $encrypted, $pubKey, OPENSSL_PKCS1_OAEP_PADDING);
        $this->assertTrue($ok, 'openssl_public_encrypt failed');

        return base64_encode($encrypted);
    }

    /** Build a fake Livewire update JSON body with the given updates map. */
    private function makeLivewirePayload(array $updates): string
    {
        return json_encode([
            'components' => [
                [
                    'snapshot' => '{}',
                    'updates'  => $updates,
                    'calls'    => [],
                ],
            ],
        ]);
    }

    /** Run the DecryptLivewirePasswords middleware against a hand-crafted request. */
    private function runMiddleware(string $body): array
    {
        $request = Request::create('/livewire/update', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], $body);

        $middleware = new DecryptLivewirePasswords();
        $captured   = [];

        $middleware->handle($request, function (Request $req) use (&$captured) {
            $captured = $req->json()->all();
            return response('ok');
        });

        return $captured;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PasswordEncryptionService
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function service_can_decrypt_a_value_encrypted_with_the_public_key(): void
    {
        $service   = app(PasswordEncryptionService::class);
        $plaintext = 'S3cure!Pass#99';

        $ciphertext = $this->encryptWithPublicKey($plaintext);
        $decrypted  = $service->decrypt($ciphertext);

        $this->assertSame($plaintext, $decrypted);
    }

    /** @test */
    public function service_throws_on_invalid_base64(): void
    {
        $this->expectException(\RuntimeException::class);

        app(PasswordEncryptionService::class)->decrypt('not-valid-base64!!!');
    }

    /** @test */
    public function service_throws_on_corrupted_ciphertext(): void
    {
        $this->expectException(\RuntimeException::class);

        // Valid base64 but not a real RSA ciphertext
        app(PasswordEncryptionService::class)->decrypt(base64_encode('this is garbage'));
    }

    /** @test */
    public function service_returns_valid_pem_public_key(): void
    {
        $pem = app(PasswordEncryptionService::class)->publicKeyPem();

        $this->assertStringContainsString('BEGIN PUBLIC KEY', $pem);
        $this->assertNotFalse(openssl_pkey_get_public($pem), 'PEM should be a loadable public key');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // DecryptLivewirePasswords middleware
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function middleware_decrypts_enc_prefixed_password_field(): void
    {
        $enc     = 'ENC:' . $this->encryptWithPublicKey('secret123');
        $payload = $this->runMiddleware($this->makeLivewirePayload(['data.password' => $enc]));

        $this->assertSame('secret123', $payload['components'][0]['updates']['data.password']);
    }

    /** @test */
    public function middleware_decrypts_enc_prefixed_current_password_field(): void
    {
        $enc     = 'ENC:' . $this->encryptWithPublicKey('oldPass!1');
        $payload = $this->runMiddleware($this->makeLivewirePayload(['data.current_password' => $enc]));

        $this->assertSame('oldPass!1', $payload['components'][0]['updates']['data.current_password']);
    }

    /** @test */
    public function middleware_leaves_non_enc_prefixed_values_unchanged(): void
    {
        // A plaintext password (shouldn't happen in prod, but middleware must not corrupt it)
        $payload = $this->runMiddleware($this->makeLivewirePayload(['data.password' => 'plaintext']));

        $this->assertSame('plaintext', $payload['components'][0]['updates']['data.password']);
    }

    /** @test */
    public function middleware_does_not_touch_non_password_keys_even_if_enc_prefixed(): void
    {
        $payload = $this->runMiddleware($this->makeLivewirePayload(['data.email' => 'ENC:something']));

        $this->assertSame('ENC:something', $payload['components'][0]['updates']['data.email']);
    }

    /** @test */
    public function middleware_leaves_value_as_is_when_decryption_fails(): void
    {
        // ENC: prefix but garbage ciphertext — should not throw, just leave it
        $raw     = 'ENC:' . base64_encode('not-real-ciphertext');
        $payload = $this->runMiddleware($this->makeLivewirePayload(['data.password' => $raw]));

        $this->assertSame($raw, $payload['components'][0]['updates']['data.password']);
    }

    /** @test */
    public function middleware_is_no_op_for_non_livewire_routes(): void
    {
        $body    = json_encode(['components' => [['updates' => ['data.password' => 'ENC:foo']]]]);
        $request = Request::create('/some/other/route', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], $body);

        $middleware = new DecryptLivewirePasswords();
        $captured   = null;

        $middleware->handle($request, function (Request $req) use (&$captured) {
            $captured = $req->json()->all();
            return response('ok');
        });

        // Should be completely unchanged
        $this->assertSame('ENC:foo', $captured['components'][0]['updates']['data.password']);
    }

    /** @test */
    public function middleware_handles_component_with_no_updates_key(): void
    {
        $body    = json_encode(['components' => [['snapshot' => '{}', 'calls' => []]]]);
        $payload = $this->runMiddleware($body);

        // Should not throw — component without updates is silently skipped
        $this->assertArrayNotHasKey('updates', $payload['components'][0]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // AdminProfile::submitEncryptedPasswordChange
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function admin_can_change_password_with_correct_encrypted_credentials(): void
    {
        $admin = $this->makeUser('committee', ['password' => Hash::make('OldPass!1')]);
        $this->actingAs($admin);

        Livewire::test(\App\Filament\Pages\Auth\AdminProfile::class)
            ->call(
                'submitEncryptedPasswordChange',
                'ENC:' . $this->encryptWithPublicKey('OldPass!1'),
                'ENC:' . $this->encryptWithPublicKey('NewPass!2'),
            )
            ->assertHasNoErrors()
            ->assertNotified();

        $this->assertTrue(Hash::check('NewPass!2', $admin->fresh()->password));
    }

    /** @test */
    public function admin_change_password_fails_with_wrong_current_password(): void
    {
        $admin = $this->makeUser('committee', ['password' => Hash::make('CorrectPass!1')]);
        $this->actingAs($admin);

        Livewire::test(\App\Filament\Pages\Auth\AdminProfile::class)
            ->call(
                'submitEncryptedPasswordChange',
                'ENC:' . $this->encryptWithPublicKey('WrongPass!1'),
                'ENC:' . $this->encryptWithPublicKey('NewPass!2'),
            )
            ->assertNotified();

        // Password must be unchanged
        $this->assertTrue(Hash::check('CorrectPass!1', $admin->fresh()->password));
    }

    /** @test */
    public function admin_change_password_fails_gracefully_with_bad_ciphertext(): void
    {
        $admin = $this->makeUser('committee', ['password' => Hash::make('OldPass!1')]);
        $this->actingAs($admin);

        Livewire::test(\App\Filament\Pages\Auth\AdminProfile::class)
            ->call(
                'submitEncryptedPasswordChange',
                'ENC:' . base64_encode('garbage'),
                'ENC:' . base64_encode('garbage'),
            )
            ->assertNotified(); // danger notification sent, no exception thrown

        $this->assertTrue(Hash::check('OldPass!1', $admin->fresh()->password));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // UserProfile::submitEncryptedPasswordChange
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function user_can_change_password_with_correct_encrypted_credentials(): void
    {
        $student = $this->makeUser('student', ['password' => Hash::make('OldPass!1')]);
        $this->actingAs($student);

        Livewire::test(\App\Filament\Pages\User\UserProfile::class)
            ->call(
                'submitEncryptedPasswordChange',
                'ENC:' . $this->encryptWithPublicKey('OldPass!1'),
                'ENC:' . $this->encryptWithPublicKey('NewPass!2'),
            )
            ->assertHasNoErrors()
            ->assertNotified();

        $this->assertTrue(Hash::check('NewPass!2', $student->fresh()->password));
    }

    /** @test */
    public function user_change_password_fails_with_wrong_current_password(): void
    {
        $student = $this->makeUser('student', ['password' => Hash::make('CorrectPass!1')]);
        $this->actingAs($student);

        Livewire::test(\App\Filament\Pages\User\UserProfile::class)
            ->call(
                'submitEncryptedPasswordChange',
                'ENC:' . $this->encryptWithPublicKey('WrongPass!1'),
                'ENC:' . $this->encryptWithPublicKey('NewPass!2'),
            )
            ->assertNotified();

        $this->assertTrue(Hash::check('CorrectPass!1', $student->fresh()->password));
    }

    /** @test */
    public function user_change_password_fails_gracefully_with_bad_ciphertext(): void
    {
        $student = $this->makeUser('student', ['password' => Hash::make('OldPass!1')]);
        $this->actingAs($student);

        Livewire::test(\App\Filament\Pages\User\UserProfile::class)
            ->call(
                'submitEncryptedPasswordChange',
                'ENC:' . base64_encode('garbage'),
                'ENC:' . base64_encode('garbage'),
            )
            ->assertNotified();

        $this->assertTrue(Hash::check('OldPass!1', $student->fresh()->password));
    }
}
