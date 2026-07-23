<?php

namespace Tests\Feature;

use App\Filament\Components\Admin\SuperAdminFeatureCards;
use App\Filament\Components\User\StudentFeatureCards;
use App\Filament\Resources\User\Catalogs\Pages\ViewCatalog;
use App\Models\MaterialAccessEvents;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
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

    public function test_profile_chooser_uses_the_saved_panel_theme(): void
    {
        User::factory()->student()->create();

        $this->assertStringContainsString(
            '@custom-variant dark (&:where(.dark, .dark *));',
            file_get_contents(resource_path('css/app.css')),
        );

        $this->get('/demo/profiles')
            ->assertOk()
            ->assertSee("const storageKey = 'stat-lms-theme';", false)
            ->assertSee('dark:bg-slate-950', false)
            ->assertSee('html.oled.dark .demo-profile-page', false);
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
        $sessionCookie = config('session.cookie');
        $chooserResponse = $this->get('/demo/profiles');
        $cookies = collect($chooserResponse->headers->getCookies())
            ->mapWithKeys(fn ($cookie): array => [
                $cookie->getName() => $chooserResponse->getCookie($cookie->getName())?->getValue(),
            ])
            ->all();
        $sessionId = $cookies[$sessionCookie] ?? null;

        $response = $this->withCookies($cookies)
            ->post('/demo/profiles', ['profile_id' => (string) $student->id])
            ->assertRedirect('/app')
            ->assertSessionHas(config('demo.profile_session_key'), $student->id);

        $this->assertNotNull($sessionId);
        $this->assertSame($sessionId, $response->getCookie($sessionCookie)?->getValue());

        $this->get('/app')->assertRedirect();
    }

    public function test_profile_chooser_locks_after_one_confirmed_submission(): void
    {
        User::factory()->student()->create();

        $this->get('/demo/profiles')
            ->assertOk()
            ->assertSee('let submissionLocked = false;', false)
            ->assertSee('if (submissionLocked) return;', false)
            ->assertSee("button?.setAttribute('disabled', '');", false)
            ->assertSee('form.submit();', false);
    }

    public function test_demo_request_dispatches_one_immediate_notification_and_disables_the_action(): void
    {
        $parent = $this->makeMaterialParent(['access_level' => 1]);
        $copy = $this->makeMaterialCopy([
            'material_parent_id' => $parent->id,
            'is_digital' => true,
            'is_available' => true,
            'file_name' => 'repo/demo-request.pdf',
        ]);
        $student = $this->makeUser('student');
        $this->actingAs($student);

        $component = Livewire::test(ViewCatalog::class, ['record' => $parent->id])
            ->callAction('requestDigital')
            ->assertActionDisabled('requestDigital');

        $dispatches = collect(data_get($component->effects, 'dispatches'))
            ->where('name', 'demo-notification')
            ->values();

        $this->assertCount(1, $dispatches);
        $this->assertSame([
            'title',
            'body',
            'status',
            'duration',
            'icon',
            'identifier',
        ], array_keys($dispatches->first()['params']['notification']));
        $this->assertSame('Digital request submitted!', $dispatches->first()['params']['notification']['title']);
        $this->assertSame('success', $dispatches->first()['params']['notification']['status']);
        $this->assertFalse(session()->has('filament.notifications'));
        $this->assertSame(1, MaterialAccessEvents::where([
            'user_id' => $student->id,
            'rr_material_id' => $copy->id,
        ])->count());
    }

    public function test_demo_viewer_uses_an_encoded_packaged_pdf_url(): void
    {
        config()->set('demo.static_asset_url', 'https://demo.example');
        $committee = $this->makeUser('committee');
        $parent = $this->makeMaterialParent([
            'access_level' => 3,
            'title' => 'Packaged PDF',
        ]);
        $copy = $this->makeMaterialCopy([
            'material_parent_id' => $parent->id,
            'is_digital' => true,
            'file_name' => 'nested/Research Paper #1.pdf',
        ]);

        $streamUrl = 'https://demo.example/pdfs/Research%20Paper%20%231.pdf';

        $this->actingAs($committee)
            ->get(route('materials.viewer', ['record' => $copy->id]))
            ->assertOk()
            ->assertSee(json_encode($streamUrl), false)
            ->assertDontSee(route('materials.stream', ['record' => $copy->id]), false);

        $this->actingAs($committee)
            ->get(route('materials.stream', ['record' => $copy->id]))
            ->assertNotFound();
    }

    public function test_demo_profile_session_authenticates_the_protected_viewer_route(): void
    {
        config()->set('demo.static_asset_url', 'https://demo.example');
        $committee = $this->makeUser('committee');
        $parent = $this->makeMaterialParent(['access_level' => 3]);
        $copy = $this->makeMaterialCopy([
            'material_parent_id' => $parent->id,
            'is_digital' => true,
            'file_name' => 'protected.pdf',
        ]);

        $this->post('/demo/profiles', ['profile_id' => (string) $committee->id])
            ->assertRedirect('/admin');

        $this->get(route('materials.viewer', ['record' => $copy->id]))
            ->assertOk()
            ->assertSee(json_encode('https://demo.example/pdfs/protected.pdf'), false);
    }

    public function test_demo_viewer_rejects_missing_filename_or_invalid_static_origin(): void
    {
        $committee = $this->makeUser('committee');
        $parent = $this->makeMaterialParent(['access_level' => 1]);
        $copy = $this->makeMaterialCopy([
            'material_parent_id' => $parent->id,
            'is_digital' => true,
            'file_name' => null,
        ]);

        config()->set('demo.static_asset_url', 'not-an-origin');
        $this->actingAs($committee)
            ->get(route('materials.viewer', ['record' => $copy->id]))
            ->assertNotFound();

        $copy->update(['file_name' => 'valid.pdf']);
        $this->actingAs($committee)
            ->get(route('materials.viewer', ['record' => $copy->id]))
            ->assertNotFound();
    }

    public function test_selecting_admin_profile_redirects_to_admin_panel(): void
    {
        $committee = User::factory()->committee()->create();

        $this->post('/demo/profiles', ['profile_id' => (string) $committee->id])
            ->assertRedirect('/admin');

        $this->get('/admin')
            ->assertOk()
            ->assertDontSee('rel="preload" href="/fonts/filament/filament/inter', false)
            ->assertSee('demo-notification-stack', false)
            ->assertSee("document.addEventListener('x-modal-opened'", false)
            ->assertSee("window.Livewire.hook('morphed'", false)
            ->assertSee('[data-fi-modal-id*="-action-"].fi-modal-open', false);
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
