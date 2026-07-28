<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TurnstileGateTest extends TestCase
{
    use RefreshDatabase;

    private const VERIFY_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'demo.enabled' => true,
            'demo.runtime' => 'server',
            'services.turnstile.enabled' => true,
            'services.turnstile.site_key' => '1x00000000000000000000AA',
            'services.turnstile.secret_key' => '1x0000000000000000000000000000000AA',
            'services.turnstile.hostname' => 'demo.example.test',
            'services.turnstile.action' => 'demo_access',
            'services.turnstile.session_key' => 'turnstile_verified_at',
            'services.turnstile.session_lifetime' => 7200,
        ]);
    }

    #[Test]
    public function guest_is_redirected_to_the_human_check_before_the_filament_login(): void
    {
        $this->get('/app/login')
            ->assertRedirect(route('turnstile.show'))
            ->assertSessionHas(
                'turnstile.intended',
                static fn (string $url): bool => str_ends_with($url, '/app/login'),
            );

        $this->get(route('turnstile.show'))
            ->assertOk()
            ->assertSee('Confirm you are human')
            ->assertSee('1x00000000000000000000AA')
            ->assertSee('data-action="demo_access"', false)
            ->assertSee('https://challenges.cloudflare.com/turnstile/v0/api.js', false)
            ->assertDontSee('1x0000000000000000000000000000000AA');
    }

    #[Test]
    public function valid_turnstile_result_grants_session_access_and_returns_to_the_intended_page(): void
    {
        Http::fake([
            self::VERIFY_URL => Http::response([
                'success' => true,
                'hostname' => 'demo.example.test',
                'action' => 'demo_access',
                'error-codes' => [],
            ]),
        ]);

        $this->withSession(['turnstile.intended' => 'http://localhost/app/login'])
            ->post(route('turnstile.verify'), [
                'cf-turnstile-response' => 'XXXX.DUMMY.TOKEN.XXXX',
            ])
            ->assertRedirect('http://localhost/app/login')
            ->assertSessionHas('turnstile_verified_at');

        $this->get('/app/login')
            ->assertOk()
            ->assertSee('User Sign In');

        Http::assertSent(function (Request $request): bool {
            return $request->url() === self::VERIFY_URL
                && $request['secret'] === '1x0000000000000000000000000000000AA'
                && $request['response'] === 'XXXX.DUMMY.TOKEN.XXXX'
                && filled($request['idempotency_key']);
        });
    }

    #[Test]
    public function failed_or_mismatched_result_does_not_grant_access(): void
    {
        Http::fake([
            self::VERIFY_URL => Http::response([
                'success' => true,
                'hostname' => 'attacker.example.test',
                'action' => 'demo_access',
                'error-codes' => [],
            ]),
        ]);

        $this->from(route('turnstile.show'))
            ->post(route('turnstile.verify'), [
                'cf-turnstile-response' => 'XXXX.DUMMY.TOKEN.XXXX',
            ])
            ->assertRedirect(route('turnstile.show'))
            ->assertSessionHasErrors('turnstile')
            ->assertSessionMissing('turnstile_verified_at');
    }

    #[Test]
    public function incomplete_enabled_configuration_fails_closed(): void
    {
        config(['services.turnstile.secret_key' => null]);

        $this->get(route('turnstile.show'))
            ->assertServiceUnavailable();
    }

    #[Test]
    public function disabled_turnstile_preserves_the_existing_demo_login_flow(): void
    {
        config(['services.turnstile.enabled' => false]);

        $this->get('/')->assertRedirect('/app/login');
        $this->get('/app/login')->assertOk();
    }

    #[Test]
    public function an_existing_authenticated_session_still_requires_the_turnstile_gate(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/')
            ->assertRedirect(route('turnstile.show'));
    }

    #[Test]
    public function operational_routes_bypass_the_human_gate(): void
    {
        $this->get('/up')->assertOk();
        $this->get(route('shared-demo.health'))->assertSuccessful();
    }

    #[Test]
    public function unverified_non_get_requests_are_rejected_instead_of_redirected(): void
    {
        $this->post(route('default-livewire.update'))->assertForbidden();
    }
}
