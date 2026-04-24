<?php

namespace Tests\Unit;

use App\Http\Middleware\EnsureProfileComplete;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class SsoMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function incomplete_user_redirected_to_onboarding(): void
    {
        Route::middleware(['auth', EnsureProfileComplete::class])
            ->get('/test-middleware-dashboard', fn () => 'ok')
            ->name('test.middleware.dashboard');

        $user = $this->makeUser('student', ['is_profile_complete' => false]);
        $this->actingAs($user);

        $this->get('/test-middleware-dashboard')
            ->assertRedirect(route('filament.user.pages.onboarding'));
    }

    /** @test */
    public function complete_user_passes_through(): void
    {
        Route::middleware(['auth', EnsureProfileComplete::class])
            ->get('/test-middleware-dashboard', fn () => 'ok')
            ->name('test.middleware.dashboard2');

        $user = $this->makeUser('student', ['is_profile_complete' => true]);
        $this->actingAs($user);

        $this->get('/test-middleware-dashboard')
            ->assertOk()
            ->assertSee('ok');
    }

    /** @test */
    public function auth_google_routes_exempted(): void
    {
        Route::middleware([EnsureProfileComplete::class])
            ->get('/test-google-exempt', fn () => 'ok')
            ->name('auth.google.exempt');

        $user = $this->makeUser('student', ['is_profile_complete' => false]);
        $this->actingAs($user);

        $this->get('/test-google-exempt')
            ->assertOk()
            ->assertSee('ok');
    }

    /** @test */
    public function onboarding_route_exempted(): void
    {
        $user = $this->makeUser('student', ['is_profile_complete' => false]);
        $this->actingAs($user);

        $this->get('/app/onboarding')->assertOk();
    }

    /** @test */
    public function guest_user_passes_through(): void
    {
        Route::middleware([EnsureProfileComplete::class])
            ->get('/test-public', fn () => 'ok')
            ->name('test.public');

        $this->get('/test-public')
            ->assertOk()
            ->assertSee('ok');
    }
}
