<?php

namespace Tests\Feature;

use App\Filament\Pages\Auth\UserLogin;
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
        putenv('DEMO_MODE=true');
        putenv('DEMO_RUNTIME=server');
        $_ENV['ASSET_URL'] = 'https://render-demo-lms-staging.cntest.uk';
        $_ENV['DEMO_MODE'] = 'true';
        $_ENV['DEMO_RUNTIME'] = 'server';
        $_SERVER['ASSET_URL'] = 'https://render-demo-lms-staging.cntest.uk';
        $_SERVER['DEMO_MODE'] = 'true';
        $_SERVER['DEMO_RUNTIME'] = 'server';

        parent::setUp();

        config()->set('demo.access_enforced', false);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        foreach (['ASSET_URL', 'DEMO_MODE', 'DEMO_RUNTIME'] as $variable) {
            putenv($variable);
            unset($_ENV[$variable], $_SERVER[$variable]);
        }
    }

    #[Test]
    public function server_demo_uses_user_login_and_google_oauth_instead_of_the_profile_chooser(): void
    {
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
        $this->get('/app/login')
            ->assertOk()
            ->assertSee('http://localhost/build/assets/', false)
            ->assertDontSee('https://render-demo-lms-staging.cntest.uk/build/assets/', false);
    }
}
