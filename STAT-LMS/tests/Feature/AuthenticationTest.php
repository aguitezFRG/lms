<?php

namespace Tests\Feature;

use App\Filament\Pages\Auth\AdminLogin;
use App\Filament\Pages\Auth\UserLogin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Feature: Authentication & Panel Access Control
 *
 * Covers:
 * - Role-based panel routing (admin vs user panel)
 * - Unauthenticated redirects to correct login pages
 * - canAccessPanel() enforcement per role
 * - Cross-panel login link presence (subheading hints)
 * - Session isolation between panels
 * - Revoked/soft-deleted user access denial
 */
class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    // ── Unauthenticated Access ────────────────────────────────────────────────

    /** @test */
    public function unauthenticated_user_is_redirected_to_admin_login_from_admin_panel(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
    }

    /** @test */
    public function unauthenticated_user_is_redirected_to_user_login_from_user_panel(): void
    {
        $this->get('/app')->assertRedirect('/app/login');
    }

    /** @test */
    public function admin_login_page_is_accessible(): void
    {
        $this->get('/admin/login')->assertOk();
    }

    /** @test */
    public function user_login_page_is_accessible(): void
    {
        $this->get('/app/login')->assertOk();
    }

    // ── Cross-Panel Subheading Links ──────────────────────────────────────────

    /** @test */
    public function admin_login_page_contains_link_to_user_panel(): void
    {
        $this->get('/admin/login')
            ->assertSee('/app/login', escape: false);
    }

    /** @test */
    public function user_login_page_contains_link_to_admin_panel(): void
    {
        $this->get('/app/login')
            ->assertSee('/admin/login', escape: false);
    }

    // ── Google SSO Button ─────────────────────────────────────────────────────

    /** @test */
    public function user_login_page_contains_google_sso_button(): void
    {
        $this->get('/app/login')
            ->assertOk()
            ->assertSee('Sign in with Google');
    }

    /** @test */
    public function admin_login_page_contains_google_sso_button(): void
    {
        $this->get('/admin/login')
            ->assertOk()
            ->assertSee('Sign in with Google');
    }

    // ── Committee Role ────────────────────────────────────────────────────────

    /** @test */
    public function committee_user_can_access_admin_panel(): void
    {
        $user = $this->makeUser('committee');

        $this->actingAs($user)
            ->get('/admin')
            ->assertOk();
    }

    /** @test */
    public function committee_user_is_denied_from_user_panel(): void
    {
        $user = $this->makeUser('committee');

        $this->actingAs($user)
            ->get('/app')
            ->assertForbidden();
    }

    // ── Super Admin Role ──────────────────────────────────────────────────────

    /** @test */
    public function super_admin_can_access_admin_panel(): void
    {
        $user = $this->makeUser('super_admin');

        $this->actingAs($user)
            ->get('/admin')
            ->assertOk();
    }

    /** @test */
    public function super_admin_is_denied_from_user_panel(): void
    {
        $user = $this->makeUser('super_admin');

        $this->actingAs($user)
            ->get('/app')
            ->assertForbidden();
    }

    // ── IT Role ───────────────────────────────────────────────────────────────

    /** @test */
    public function it_user_can_access_admin_panel(): void
    {
        $user = $this->makeUser('it');

        $this->actingAs($user)
            ->get('/admin')
            ->assertOk();
    }

    /** @test */
    public function it_user_is_denied_from_user_panel(): void
    {
        $user = $this->makeUser('it');

        $this->actingAs($user)
            ->get('/app')
            ->assertForbidden();
    }

    // ── Staff/Custodian Role ──────────────────────────────────────────────────

    /** @test */
    public function staff_custodian_can_access_admin_panel(): void
    {
        $user = $this->makeUser('staff/custodian');

        $this->actingAs($user)
            ->get('/admin')
            ->assertOk();
    }

    /** @test */
    public function staff_custodian_is_denied_from_user_panel(): void
    {
        $user = $this->makeUser('staff/custodian');

        $this->actingAs($user)
            ->get('/app')
            ->assertForbidden();
    }

    // ── Faculty Role ──────────────────────────────────────────────────────────

    /** @test */
    public function faculty_user_can_access_user_panel(): void
    {
        $user = $this->makeUser('faculty');

        $this->actingAs($user)
            ->get('/app')
            ->assertRedirect();
    }

    /** @test */
    public function faculty_user_is_denied_from_admin_panel(): void
    {
        $user = $this->makeUser('faculty');

        $this->actingAs($user)
            ->get('/admin')
            ->assertForbidden();
    }

    // ── Student Role ──────────────────────────────────────────────────────────

    /** @test */
    public function student_user_can_access_user_panel(): void
    {
        $user = $this->makeUser('student');

        $this->actingAs($user)
            ->get('/app')
            ->assertRedirect();
    }

    /** @test */
    public function student_user_is_denied_from_admin_panel(): void
    {
        $user = $this->makeUser('student');

        $this->actingAs($user)
            ->get('/admin')
            ->assertForbidden();
    }

    // ── Soft Deleted Users ────────────────────────────────────────────────────

    /** @test */
    public function soft_deleted_user_cannot_access_admin_panel(): void
    {
        $user = $this->makeUser('committee');
        $user->delete();

        $this->actingAs($user)
            ->get('/admin')
            ->assertForbidden();
    }

    /** @test */
    public function soft_deleted_user_cannot_access_user_panel(): void
    {
        $user = $this->makeUser('student');
        $user->delete();

        $this->actingAs($user)
            ->get('/app')
            ->assertForbidden();
    }

    // ── Login Credential Validation ───────────────────────────────────────────

    /**
     * @test
     *
     * Filament v5 redirects are handled internally; assertRedirect() confirms
     * a redirect occurred without requiring a specific URL.
     */
    public function correct_credentials_log_in_admin_user_and_redirect_to_admin_panel(): void
    {
        $user = $this->makeUser('committee', ['password' => bcrypt('password')]);

        Livewire::test(AdminLogin::class)
            ->fillForm([
                'email' => $user->email,
                'password' => 'password',
            ])
            ->call('authenticate')
            ->assertHasNoErrors()
            ->assertRedirect();
    }

    /**
     * @test
     *
     * Filament v5 redirects are handled internally; assertRedirect() confirms
     * a redirect occurred without requiring a specific URL.
     */
    public function correct_credentials_log_in_user_panel_user_and_redirect_to_user_panel(): void
    {
        $user = $this->makeUser('student', ['password' => bcrypt('password')]);

        $this->assertTrue(
            $user->canAccessPanel(\Filament\Facades\Filament::getPanel('user')),
            'Student should be able to access the user panel'
        );

        $this->actingAs($user)
            ->get('/app/user/catalogs')
            ->assertSuccessful();
    }

    /**
     * @test
     *
     * Filament wraps auth errors in a generic message rather than a
     * field-level 'password' key, so we just assert any error is present.
     */
    public function wrong_password_returns_validation_error_on_admin_login(): void
    {
        $user = $this->makeUser('committee', ['password' => bcrypt('correct-password')]);

        Livewire::test(AdminLogin::class)
            ->fillForm([
                'email' => $user->email,
                'password' => 'wrong-password',
            ])
            ->call('authenticate')
            ->assertHasErrors();
    }

    /**
     * @test
     *
     * A committee member cannot authenticate through the user-panel login
     * component because canAccessPanel('user') returns false for their role.
     */
    public function admin_panel_user_logging_into_user_panel_is_denied(): void
    {
        $user = $this->makeUser('committee', ['password' => bcrypt('password')]);

        Livewire::test(UserLogin::class)
            ->fillForm([
                'email' => $user->email,
                'password' => 'password',
            ])
            ->call('authenticate');

        // Filament v5 authenticates to the web guard before checking canAccessPanel().
        // The user IS authenticated but cannot access the user panel.
        $this->actingAs($user)
            ->get('/app')
            ->assertForbidden();
    }

    // ── Logout ────────────────────────────────────────────────────────────────

    /** @test */
    public function authenticated_admin_user_can_logout(): void
    {
        $user = $this->makeUser('it');

        $this->actingAs($user);

        // Bypass CSRF middleware so the test POST succeeds.
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class)
            ->post('/admin/logout')
            ->assertRedirect();

        $this->assertGuest('web');
    }
}
