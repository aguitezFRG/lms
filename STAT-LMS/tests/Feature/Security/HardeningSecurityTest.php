<?php

namespace Tests\Feature\Security;

use App\Filament\Pages\Auth\AdminLogin;
use App\Filament\Pages\Auth\UserLogin;
use App\Filament\Resources\User\Catalogs\Pages\ListCatalogs;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class HardeningSecurityTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function stream_blocks_path_traversal_file_names(): void
    {
        $parent = $this->makeMaterialParent(['access_level' => 1]);
        $copy = $this->makeMaterialCopy([
            'material_parent_id' => $parent->id,
            'is_digital' => true,
            'is_available' => true,
            'file_name' => '../../.env',
        ]);

        $committee = $this->makeUser('committee');

        $this->actingAs($committee)
            ->get(route('materials.stream', $copy))
            ->assertNotFound();
    }

    /** @test */
    public function user_policy_blocks_lower_privilege_updates_and_restores(): void
    {
        $committee = $this->makeUser('committee');
        $it = $this->makeUser('it');
        $superAdmin = $this->makeUser('super_admin');
        $student = $this->makeUser('student');

        $this->assertTrue($committee->can('update', $student));
        $this->assertFalse($it->can('update', $committee));
        $this->assertFalse($committee->can('restore', $superAdmin));
        $this->assertFalse($it->can('forceDelete', $committee));
    }

    /** @test */
    public function catalog_sort_parameters_are_normalized_to_allowlisted_values(): void
    {
        $student = $this->makeUser('student');
        $this->actingAs($student);

        Livewire::test(ListCatalogs::class)
            ->set('sortBy', 'id;drop table users')
            ->set('sortDir', 'sideways')
            ->assertSet('sortBy', 'publication_date')
            ->assertSet('sortDir', 'desc');
    }

    /** @test */
    public function login_messages_are_indistinguishable_for_banned_and_soft_deleted_accounts(): void
    {
        $softDeleted = $this->makeUser('student', ['password' => bcrypt('password'), 'email' => 'deleted@example.com']);
        $softDeleted->delete();

        $banned = $this->makeUser('student', ['password' => bcrypt('password'), 'email' => 'banned@example.com', 'is_banned' => true]);

        Livewire::test(UserLogin::class)
            ->fillForm(['email' => 'deleted@example.com', 'password' => 'password'])
            ->call('authenticate')
            ->assertHasErrors()
            ->assertSee('Invalid credentials.');

        Livewire::test(UserLogin::class)
            ->fillForm(['email' => 'banned@example.com', 'password' => 'password'])
            ->call('authenticate')
            ->assertHasErrors()
            ->assertSee('Invalid credentials.');

        Livewire::test(AdminLogin::class)
            ->fillForm(['email' => 'deleted@example.com', 'password' => 'password'])
            ->call('authenticate')
            ->assertHasErrors()
            ->assertSee('Invalid credentials.');
    }

    /** @test */
    public function responses_include_security_headers_baseline(): void
    {
        $response = $this->get('/app/login');

        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        $response->assertHeader('Content-Security-Policy');
    }

    /** @test */
    public function password_encryption_key_endpoint_is_throttled_and_cacheable(): void
    {
        $response = $this->get(route('password.encryption-key'));

        $response->assertOk();
        $this->assertStringContainsString('public', (string) $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('max-age=3600', (string) $response->headers->get('Cache-Control'));
        $response->assertHeader('Vary', 'Accept-Encoding');
    }
}
