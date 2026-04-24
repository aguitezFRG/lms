<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Tests\TestCase;

class SsoAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function redirect_route_returns_socialite_redirect(): void
    {
        $provider = Mockery::mock(\Laravel\Socialite\Contracts\Provider::class);
        $provider->shouldReceive('redirect')->andReturn(redirect('https://accounts.google.com/oauth'));

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        $this->get(route('auth.google.redirect'))
            ->assertRedirect('https://accounts.google.com/oauth');
    }

    /** @test */
    public function callback_creates_new_student_user(): void
    {
        $googleUser = Mockery::mock(\Laravel\Socialite\Contracts\User::class);
        $googleUser->shouldReceive('getId')->andReturn('123456789');
        $googleUser->shouldReceive('getEmail')->andReturn('newstudent@example.com');
        $googleUser->shouldReceive('getName')->andReturn('New Student');
        $googleUser->shouldReceive('user')->andReturn(['given_name' => 'New', 'family_name' => 'Student']);

        $provider = Mockery::mock(\Laravel\Socialite\Contracts\Provider::class);
        $provider->shouldReceive('user')->andReturn($googleUser);

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('filament.user.pages.onboarding'));

        $this->assertDatabaseHas('users', [
            'email' => 'newstudent@example.com',
            'google_id' => '123456789',
            'role' => UserRole::STUDENT->value,
            'is_profile_complete' => false,
        ]);

        $user = User::where('email', 'newstudent@example.com')->first();
        $this->assertNotNull($user);
        $this->assertNotNull($user->email_verified_at);
        $this->assertAuthenticated();
        $this->assertEquals($user->id, auth()->id());
    }

    /** @test */
    public function callback_links_existing_user_by_email(): void
    {
        $user = $this->makeUser('faculty', [
            'email' => 'existing@example.com',
            'google_id' => null,
            'is_profile_complete' => true,
        ]);

        $googleUser = Mockery::mock(\Laravel\Socialite\Contracts\User::class);
        $googleUser->shouldReceive('getId')->andReturn('987654321');
        $googleUser->shouldReceive('getEmail')->andReturn('existing@example.com');
        $googleUser->shouldReceive('getName')->andReturn('Existing User');
        $googleUser->shouldReceive('user')->andReturn(['given_name' => 'Existing', 'family_name' => 'User']);

        $provider = Mockery::mock(\Laravel\Socialite\Contracts\Provider::class);
        $provider->shouldReceive('user')->andReturn($googleUser);

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        $this->get(route('auth.google.callback'))
            ->assertRedirect('/app');

        $user->refresh();
        $this->assertEquals('987654321', $user->google_id);
        $this->assertAuthenticated();
        $this->assertEquals($user->id, auth()->id());
    }

    /** @test */
    public function callback_rejects_soft_deleted_user(): void
    {
        $user = $this->makeUser('student', [
            'email' => 'deleted@example.com',
            'google_id' => null,
        ]);
        $user->delete();

        $googleUser = Mockery::mock(\Laravel\Socialite\Contracts\User::class);
        $googleUser->shouldReceive('getId')->andReturn('999999');
        $googleUser->shouldReceive('getEmail')->andReturn('deleted@example.com');
        $googleUser->shouldReceive('getName')->andReturn('Deleted User');
        $googleUser->shouldReceive('user')->andReturn(['given_name' => 'Deleted', 'family_name' => 'User']);

        $provider = Mockery::mock(\Laravel\Socialite\Contracts\Provider::class);
        $provider->shouldReceive('user')->andReturn($googleUser);

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        $this->get(route('auth.google.callback'))
            ->assertRedirect('/app/login')
            ->assertSessionHas('error');

        $this->assertGuest('web');
    }

    /** @test */
    public function callback_handles_oauth_failure(): void
    {
        $provider = Mockery::mock(\Laravel\Socialite\Contracts\Provider::class);
        $provider->shouldReceive('user')->andThrow(new \Exception('OAuth error'));

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        $this->get(route('auth.google.callback'))
            ->assertRedirect('/app/login')
            ->assertSessionHas('error');

        $this->assertGuest('web');
    }

    /** @test */
    public function sso_button_present_on_user_login_page(): void
    {
        $this->get('/app/login')
            ->assertOk()
            ->assertSee('Sign in with Google');
    }

    /** @test */
    public function sso_button_present_on_admin_login_page(): void
    {
        $this->get('/admin/login')
            ->assertOk()
            ->assertSee('Sign in with Google');
    }

    /** @test */
    public function new_sso_user_redirected_to_onboarding(): void
    {
        $googleUser = Mockery::mock(\Laravel\Socialite\Contracts\User::class);
        $googleUser->shouldReceive('getId')->andReturn('111111');
        $googleUser->shouldReceive('getEmail')->andReturn('newbie@example.com');
        $googleUser->shouldReceive('getName')->andReturn('Newbie User');
        $googleUser->shouldReceive('user')->andReturn(['given_name' => 'Newbie', 'family_name' => 'User']);

        $provider = Mockery::mock(\Laravel\Socialite\Contracts\Provider::class);
        $provider->shouldReceive('user')->andReturn($googleUser);

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('filament.user.pages.onboarding'));
    }

    /** @test */
    public function existing_complete_user_redirected_to_app(): void
    {
        $user = $this->makeUser('student', [
            'email' => 'complete@example.com',
            'google_id' => null,
            'is_profile_complete' => true,
        ]);

        $googleUser = Mockery::mock(\Laravel\Socialite\Contracts\User::class);
        $googleUser->shouldReceive('getId')->andReturn('222222');
        $googleUser->shouldReceive('getEmail')->andReturn('complete@example.com');
        $googleUser->shouldReceive('getName')->andReturn('Complete User');
        $googleUser->shouldReceive('user')->andReturn(['given_name' => 'Complete', 'family_name' => 'User']);

        $provider = Mockery::mock(\Laravel\Socialite\Contracts\Provider::class);
        $provider->shouldReceive('user')->andReturn($googleUser);

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        $this->get(route('auth.google.callback'))
            ->assertRedirect('/app');
    }

    /** @test */
    public function existing_admin_user_redirected_to_admin(): void
    {
        $user = $this->makeUser('committee', [
            'email' => 'admin@example.com',
            'google_id' => '333333',
            'is_profile_complete' => true,
        ]);

        $googleUser = Mockery::mock(\Laravel\Socialite\Contracts\User::class);
        $googleUser->shouldReceive('getId')->andReturn('333333');
        $googleUser->shouldReceive('getEmail')->andReturn('admin@example.com');
        $googleUser->shouldReceive('getName')->andReturn('Admin User');
        $googleUser->shouldReceive('user')->andReturn(['given_name' => 'Admin', 'family_name' => 'User']);

        $provider = Mockery::mock(\Laravel\Socialite\Contracts\Provider::class);
        $provider->shouldReceive('user')->andReturn($googleUser);

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        $this->get(route('auth.google.callback'))
            ->assertRedirect('/admin');
    }
}
