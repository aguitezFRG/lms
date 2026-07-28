<?php

namespace Tests\Unit;

use App\Http\Middleware\EnsureProfileComplete;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SsoMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
    public function onboarding_route_exempted(): void
    {
        $user = $this->makeUser('student', ['is_profile_complete' => false]);
        $this->actingAs($user);

        $this->get('/app/onboarding')->assertOk();
    }

    #[Test]
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
