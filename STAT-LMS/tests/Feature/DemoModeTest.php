<?php

namespace Tests\Feature;

use App\Filament\Components\Admin\SuperAdminFeatureCards;
use App\Filament\Components\User\StudentFeatureCards;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoModeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        putenv('DEMO_MODE=true');
        putenv('DEMO_DATABASE_PATH=:memory:');
        $_ENV['DEMO_MODE'] = 'true';
        $_ENV['DEMO_DATABASE_PATH'] = ':memory:';
        $_SERVER['DEMO_MODE'] = 'true';
        $_SERVER['DEMO_DATABASE_PATH'] = ':memory:';

        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        putenv('DEMO_MODE');
        putenv('DEMO_DATABASE_PATH');
        unset(
            $_ENV['DEMO_MODE'],
            $_ENV['DEMO_DATABASE_PATH'],
            $_SERVER['DEMO_MODE'],
            $_SERVER['DEMO_DATABASE_PATH'],
        );
    }

    public function test_profile_chooser_lists_active_profiles_without_credentials(): void
    {
        $active = User::factory()->student()->create();
        $banned = User::factory()->faculty()->create(['is_banned' => true]);

        $this->get('/demo/profiles')
            ->assertOk()
            ->assertSee($active->name)
            ->assertDontSee($banned->name)
            ->assertDontSee('password', false);
    }

    public function test_profile_switch_dialog_is_centered_in_the_viewport(): void
    {
        User::factory()->student()->create();

        $this->get('/demo/profiles')
            ->assertOk()
            ->assertSee('id="profile-switch-dialog"', false)
            ->assertSee('inset: 0;', false)
            ->assertSee('margin: auto;', false);
    }

    public function test_onboarding_cards_stay_inside_the_php_runtime(): void
    {
        $this->assertStringContainsString(
            'href="/__php/admin/users"',
            SuperAdminFeatureCards::render(),
        );
        $this->assertStringContainsString(
            'href="/__php/app/user/catalogs"',
            StudentFeatureCards::render(),
        );

        config()->set('demo.enabled', false);

        $this->assertStringContainsString(
            'href="/admin/users"',
            SuperAdminFeatureCards::render(),
        );
    }

    public function test_selecting_student_profile_redirects_to_user_panel_and_authenticates_following_requests(): void
    {
        $student = User::factory()->student()->create();

        $this->post('/demo/profiles', ['profile_id' => (string) $student->id])
            ->assertRedirect('/app')
            ->assertSessionHas(config('demo.profile_session_key'), $student->id);

        $this->get('/app')->assertRedirect();
    }

    public function test_selecting_admin_profile_redirects_to_admin_panel(): void
    {
        $committee = User::factory()->committee()->create();

        $this->post('/demo/profiles', ['profile_id' => (string) $committee->id])
            ->assertRedirect('/admin');

        $this->get('/admin')->assertOk();
    }

    public function test_super_admin_profile_hides_the_role_view_selector(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $this->post('/demo/profiles', ['profile_id' => (string) $superAdmin->id])
            ->assertRedirect('/admin');

        $this->get('/admin')
            ->assertOk()
            ->assertDontSee('rr-role-view-switcher', false);
    }

    public function test_banned_profile_cannot_be_selected(): void
    {
        $banned = User::factory()->student()->create(['is_banned' => true]);

        $this->post('/demo/profiles', ['profile_id' => (string) $banned->id])->assertNotFound();
    }

    public function test_demo_mode_hides_login_and_external_authentication_routes(): void
    {
        $this->get('/app/login')->assertNotFound();
        $this->get('/admin/login')->assertNotFound();
        $this->get('/auth/google/redirect')->assertNotFound();
        $this->get('/password-encryption-key')->assertNotFound();
    }

    public function test_demo_routes_are_unavailable_when_demo_mode_is_disabled(): void
    {
        config()->set('demo.enabled', false);

        $this->get('/demo/profiles')->assertNotFound();
    }
}
