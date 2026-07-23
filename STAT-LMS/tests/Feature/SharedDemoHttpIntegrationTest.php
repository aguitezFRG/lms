<?php

namespace Tests\Feature;

use App\Http\Middleware\VerifyCloudflareAccess;
use App\Models\SharedDemoRuntimeState;
use App\Services\PdfWatermarkService;
use Carbon\Carbon;
use Firebase\JWT\JWT;
use Illuminate\Console\Command;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Tests\TestCase;

class SharedDemoHttpIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private const MATERIAL_DISK = 'shared-demo-http-test';

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(self::MATERIAL_DISK);
        Http::preventStrayRequests();
        config([
            'demo.material_disk' => self::MATERIAL_DISK,
            'demo.access_enforced' => false,
        ]);
    }

    #[Test]
    public function reset_endpoint_rejects_missing_and_invalid_hmac_headers(): void
    {
        $this->enableServerRuntime();
        config(['demo.reset_hmac_secret' => 'test-reset-secret']);

        $this->postJson(route('shared-demo.reset'))
            ->assertStatus(422);

        $this->withHeaders([
            'X-Demo-Reset-Key' => 'daily-reset',
            'X-Demo-Reset-Signature' => str_repeat('0', 64),
        ])->postJson(route('shared-demo.reset'))
            ->assertForbidden();
    }

    #[Test]
    public function reset_endpoint_accepts_a_valid_hmac_and_runs_the_reset(): void
    {
        $this->enableServerRuntime();
        config(['demo.reset_hmac_secret' => 'test-reset-secret']);

        $this->assertSame(
            Command::SUCCESS,
            Artisan::call('demo:bootstrap-shared', ['--force' => true]),
        );

        Storage::disk(self::MATERIAL_DISK)->put('uploads/http-reset.pdf', 'mutable');

        $idempotencyKey = 'render-cron-2026-07-23';
        $signature = hash_hmac('sha256', $idempotencyKey, 'test-reset-secret');

        $this->withHeaders([
            'X-Demo-Reset-Key' => $idempotencyKey,
            'X-Demo-Reset-Signature' => $signature,
        ])->postJson(route('shared-demo.reset'))
            ->assertOk()
            ->assertJson([
                'ok' => true,
                'idempotency_key' => $idempotencyKey,
            ]);

        Storage::disk(self::MATERIAL_DISK)->assertMissing('uploads/http-reset.pdf');

        $state = SharedDemoRuntimeState::findOrFail(1);
        $this->assertSame('ready', $state->status);
        $this->assertSame($idempotencyKey, $state->last_idempotency_key);
    }

    #[Test]
    public function health_endpoint_returns_ok_when_ready_and_service_unavailable_during_maintenance(): void
    {
        $this->enableServerRuntime();
        Artisan::call('demo:bootstrap-shared', ['--force' => true]);

        $this->getJson(route('shared-demo.health'))
            ->assertOk()
            ->assertJsonPath('healthy', true)
            ->assertJsonPath('runtime_state.status', 'ready');

        SharedDemoRuntimeState::findOrFail(1)->forceFill([
            'status' => 'resetting',
            'maintenance' => true,
        ])->save();

        $this->getJson(route('shared-demo.health'))
            ->assertServiceUnavailable()
            ->assertJsonPath('healthy', false)
            ->assertJsonPath('runtime_state.maintenance', true);
    }

    #[Test]
    public function cloudflare_access_rejects_incomplete_configuration_before_token_validation(): void
    {
        $this->enableCloudflareAccess();
        config([
            'demo.access_team_domain' => '',
            'demo.access_audience' => '',
        ]);

        $this->assertCloudflareFailure([], 503);
    }

    #[Test]
    public function cloudflare_access_rejects_a_missing_token(): void
    {
        $this->enableCloudflareAccess();

        $this->assertCloudflareFailure([], 403);
    }

    #[Test]
    public function cloudflare_access_accepts_a_locally_signed_token_from_faked_jwks(): void
    {
        $this->enableCloudflareAccess();

        $keyOptions = [
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ];
        foreach ([
            getenv('OPENSSL_CONF') ?: null,
            '/etc/ssl/openssl.cnf',
            '/etc/pki/tls/openssl.cnf',
        ] as $configPath) {
            if (is_string($configPath) && is_file($configPath)) {
                $keyOptions['config'] = $configPath;

                break;
            }
        }

        $key = openssl_pkey_new($keyOptions);
        $this->assertNotFalse($key);

        $this->assertTrue(openssl_pkey_export($key, $privateKey, null, $keyOptions));

        $details = openssl_pkey_get_details($key);
        $this->assertIsArray($details);

        $teamDomain = 'team.cloudflareaccess.test';
        $audience = 'shared-demo-audience';
        $keyId = 'test-key';
        $jwk = [
            'kty' => 'RSA',
            'use' => 'sig',
            'alg' => 'RS256',
            'kid' => $keyId,
            'n' => $this->base64UrlEncode($details['rsa']['n']),
            'e' => $this->base64UrlEncode($details['rsa']['e']),
        ];

        config([
            'demo.access_team_domain' => $teamDomain,
            'demo.access_audience' => $audience,
        ]);

        Http::fake([
            "https://{$teamDomain}/cdn-cgi/access/certs" => Http::response([
                'keys' => [$jwk],
            ]),
        ]);

        $token = JWT::encode([
            'iss' => "https://{$teamDomain}",
            'aud' => [$audience],
            'sub' => 'test-user',
            'iat' => time() - 5,
            'nbf' => time() - 5,
            'exp' => time() + 300,
        ], $privateKey, 'RS256', $keyId);

        $response = $this->invokeCloudflare([
            'Cf-Access-Jwt-Assertion' => $token,
        ]);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('allowed', $response->getContent());
        Http::assertSentCount(1);
    }

    #[Test]
    public function server_runtime_streams_material_from_the_configured_fake_disk(): void
    {
        $this->enableServerRuntime();

        $pdf = "%PDF-1.4\n1 0 obj<</Type/Catalog>>endobj\n%%EOF";
        $watermarked = "%PDF-1.4\nwatermarked\n%%EOF";
        $objectKey = 'seed/repository/access_level_1/server-test.pdf';

        $parent = $this->makeMaterialParent([
            'access_level' => 1,
            'title' => 'Server Disk Material',
        ]);
        $copy = $this->makeMaterialCopy([
            'material_parent_id' => $parent->id,
            'is_digital' => true,
            'is_available' => true,
            'file_name' => $objectKey,
        ]);
        $committee = $this->makeUser('committee');

        Storage::disk(self::MATERIAL_DISK)->put($objectKey, $pdf);
        Storage::fake('local');

        $watermarkService = $this->mock(PdfWatermarkService::class);
        $watermarkService->shouldReceive('watermark')
            ->once()
            ->withArgs(function (
                string $path,
                $user,
                string $title,
                Carbon $accessedAt,
            ) use ($committee, $pdf): bool {
                return is_file($path)
                    && file_get_contents($path) === $pdf
                    && $user->is($committee)
                    && $title === 'Server Disk Material'
                    && $accessedAt->timezoneName === 'Asia/Manila';
            })
            ->andReturn($watermarked);

        $this->actingAs($committee)
            ->get(route('materials.stream', $copy))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertContent($watermarked);
    }

    private function enableServerRuntime(): void
    {
        config([
            'demo.enabled' => true,
            'demo.runtime' => 'server',
            'demo.material_disk' => self::MATERIAL_DISK,
            'demo.access_enforced' => false,
        ]);
    }

    private function enableCloudflareAccess(): void
    {
        config([
            'demo.enabled' => true,
            'demo.runtime' => 'server',
            'demo.access_enforced' => true,
            'demo.access_team_domain' => 'team.cloudflareaccess.test',
            'demo.access_audience' => 'shared-demo-audience',
        ]);
    }

    /**
     * @param  array<string, string>  $headers
     */
    private function assertCloudflareFailure(array $headers, int $status): void
    {
        try {
            $this->invokeCloudflare($headers);
            $this->fail("Cloudflare Access middleware should have returned HTTP {$status}.");
        } catch (HttpExceptionInterface $exception) {
            $this->assertSame($status, $exception->getStatusCode());
        }
    }

    /**
     * @param  array<string, string>  $headers
     */
    private function invokeCloudflare(array $headers): Response
    {
        $request = Request::create('/protected', 'GET');
        $request->headers->add($headers);

        return app(VerifyCloudflareAccess::class)->handle(
            $request,
            fn (): Response => response('allowed'),
        );
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
