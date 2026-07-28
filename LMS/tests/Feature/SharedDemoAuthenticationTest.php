<?php

namespace Tests\Feature;

use App\Filament\Pages\Auth\UserLogin;
use App\Filament\Pages\User\UserOnboarding;
use App\Models\User;
use Database\Seeders\DemoDatabaseSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SharedDemoAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        putenv('ASSET_URL=https://render-demo-lms-staging.cntest.uk');
        putenv('APP_FORCE_HTTPS=true');
        putenv('DEMO_MODE=true');
        putenv('DEMO_RUNTIME=server');
        $_ENV['ASSET_URL'] = 'https://render-demo-lms-staging.cntest.uk';
        $_ENV['APP_FORCE_HTTPS'] = 'true';
        $_ENV['DEMO_MODE'] = 'true';
        $_ENV['DEMO_RUNTIME'] = 'server';
        $_SERVER['ASSET_URL'] = 'https://render-demo-lms-staging.cntest.uk';
        $_SERVER['APP_FORCE_HTTPS'] = 'true';
        $_SERVER['DEMO_MODE'] = 'true';
        $_SERVER['DEMO_RUNTIME'] = 'server';

        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        foreach (['APP_FORCE_HTTPS', 'ASSET_URL', 'DEMO_MODE', 'DEMO_RUNTIME'] as $variable) {
            putenv($variable);
            unset($_ENV[$variable], $_SERVER[$variable]);
        }
    }

    #[Test]
    public function server_demo_uses_user_login_and_google_oauth_instead_of_the_profile_chooser(): void
    {
        $this->assertFalse(config('demo.access_enforced'));

        $this->get('/')->assertRedirect('/app/login');
        $this->get('/demo/profiles')->assertNotFound();

        $this->get('/app/login')
            ->assertOk()
            ->assertSee('User Sign In')
            ->assertSee('Sign in with Google')
            ->assertSee('carlos.student@demo.lms')
            ->assertSee('ricardo.faculty@demo.lms');

        $this->assertTrue(route('auth.google.redirect') !== '');
        $this->get('/password-encryption-key')->assertSuccessful();
    }

    #[Test]
    public function server_demo_stages_livewire_uploads_locally_before_supabase_persistence(): void
    {
        config(['demo.material_disk' => 'supabase']);

        $this->assertSame('local', config('livewire.temporary_file_upload.disk'));
        $this->assertNotSame(
            config('demo.material_disk'),
            config('livewire.temporary_file_upload.disk'),
        );
    }

    #[Test]
    public function google_redirect_fails_cleanly_when_oauth_credentials_are_missing(): void
    {
        config([
            'services.google.client_id' => null,
            'services.google.client_secret' => null,
        ]);

        $this->get('/auth/google/redirect')
            ->assertRedirect('/app/login')
            ->assertSessionHas('error', 'Google sign-in is temporarily unavailable.');
    }

    #[Test]
    public function stale_cloudflare_access_environment_cannot_block_shared_demo_web_routes(): void
    {
        config([
            'demo.access_enforced' => true,
            'demo.access_team_domain' => 'stale.cloudflareaccess.test',
            'demo.access_audience' => 'stale-audience',
        ]);

        $this->get('/')->assertRedirect('/app/login');
    }

    #[Test]
    public function server_demo_exposes_predefined_admin_credentials(): void
    {
        $this->get('/admin/login')
            ->assertOk()
            ->assertSee('Admin Sign In')
            ->assertSee('Sign in with Google')
            ->assertSee('committee@demo.lms')
            ->assertSee('custodian@demo.lms');
    }

    #[Test]
    public function server_demo_can_sign_in_with_a_predefined_seeded_credential(): void
    {
        app(DemoDatabaseSeeder::class)->run();

        $student = User::query()->where('email', 'carlos.student@demo.lms')->firstOrFail();

        $this->assertTrue(Hash::check('password', $student->password));

        $this->get('/app/login')->assertOk();
        Filament::setCurrentPanel(Filament::getPanel('user'));

        Livewire::test(UserLogin::class)
            ->set('data.email', $student->email)
            ->set('data.password', 'password')
            ->call('authenticate')
            ->assertHasNoErrors();

        $this->assertAuthenticatedAs($student);
    }

    #[Test]
    public function server_demo_assets_use_the_current_request_origin(): void
    {
        $this->assertSame('/lms_favicon.png', Filament::getPanel('admin')->getFavicon());
        $this->assertSame('/lms_favicon.png', Filament::getPanel('user')->getFavicon());
        $this->assertFileExists(public_path('lms_favicon.png'));
        $this->assertStringStartsWith("\x89PNG\r\n\x1a\n", file_get_contents(public_path('lms_favicon.png')));

        $favicon = imagecreatefrompng(public_path('lms_favicon.png'));
        $this->assertNotFalse($favicon);
        $this->assertSame(512, imagesx($favicon));
        $this->assertSame(512, imagesy($favicon));
        $corner = imagecolorsforindex($favicon, imagecolorat($favicon, 0, 0));
        $this->assertSame(127, $corner['alpha']);
        imagedestroy($favicon);
        $this->assertFileExists(public_path('images/lms.png'));

        $this->get('/app/login')
            ->assertOk()
            ->assertSee('/build/assets/', false)
            ->assertSee('src="/images/lms.png"', false)
            ->assertSee('alt="LMS logo"', false)
            ->assertSee('href="/lms_favicon.png"', false)
            ->assertDontSee('http://localhost/images/lms.png', false)
            ->assertDontSee('https://render-demo-lms-staging.cntest.uk/build/assets/', false);
    }

    #[Test]
    public function server_demo_disables_automatic_livewire_polling(): void
    {
        app(DemoDatabaseSeeder::class)->run();

        $student = User::query()->where('email', 'carlos.student@demo.lms')->firstOrFail();

        $this->assertFalse(config('demo.polling_enabled'));

        $this->actingAs($student)
            ->get(UserOnboarding::getUrl())
            ->assertOk()
            ->assertDontSee('wire:poll', false);
    }

    #[Test]
    public function server_demo_disables_admin_dashboard_polling(): void
    {
        app(DemoDatabaseSeeder::class)->run();

        $committee = User::query()->where('email', 'committee@demo.lms')->firstOrFail();

        $this->actingAs($committee)
            ->get('/admin')
            ->assertOk()
            ->assertDontSee('wire:poll', false);
    }
}
