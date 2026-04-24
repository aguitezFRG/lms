<?php

namespace Tests\Feature;

use App\Filament\Pages\Onboarding\CompleteProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SsoOnboardingTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function onboarding_page_requires_auth(): void
    {
        $this->get('/app/onboarding')->assertRedirect('/app/login');
    }

    /** @test */
    public function incomplete_user_can_access_onboarding(): void
    {
        $user = $this->makeUser('student', ['is_profile_complete' => false]);
        $this->actingAs($user);

        $this->get('/app/onboarding')->assertOk();
    }

    /** @test */
    public function complete_user_redirected_from_onboarding(): void
    {
        $user = $this->makeUser('student', ['is_profile_complete' => true]);
        $this->actingAs($user);

        $this->get('/app/onboarding')->assertRedirect('/app');
    }

    /** @test */
    public function step_one_validates_required_name_fields(): void
    {
        $user = $this->makeUser('student', ['is_profile_complete' => false]);
        $this->actingAs($user);

        Livewire::test(CompleteProfile::class)
            ->set('data.f_name', '')
            ->set('data.l_name', '')
            ->call('nextStep')
            ->assertHasErrors(['data.f_name' => 'required', 'data.l_name' => 'required']);

        // m_name is optional
        Livewire::test(CompleteProfile::class)
            ->set('data.f_name', 'John')
            ->set('data.l_name', 'Doe')
            ->set('data.m_name', '')
            ->call('nextStep')
            ->assertHasNoErrors()
            ->assertSet('step', 2);
    }

    /** @test */
    public function step_two_validates_student_number_format(): void
    {
        $existingUser = $this->makeUser('student', ['std_number' => '2020-12345']);

        $user = $this->makeUser('student', [
            'is_profile_complete' => false,
            'std_number' => null,
        ]);
        $this->actingAs($user);

        // Invalid format
        Livewire::test(CompleteProfile::class)
            ->set('data.f_name', 'John')
            ->set('data.l_name', 'Doe')
            ->call('nextStep')
            ->set('data.std_number', '12345')
            ->call('submit')
            ->assertHasErrors(['data.std_number']);

        // Duplicate number
        Livewire::test(CompleteProfile::class)
            ->set('data.f_name', 'John')
            ->set('data.l_name', 'Doe')
            ->call('nextStep')
            ->set('data.std_number', '2020-12345')
            ->call('submit')
            ->assertHasErrors(['data.std_number']);
    }

    /** @test */
    public function step_two_allows_null_student_number(): void
    {
        $user = $this->makeUser('student', [
            'is_profile_complete' => false,
            'std_number' => null,
        ]);
        $this->actingAs($user);

        Livewire::test(CompleteProfile::class)
            ->set('data.f_name', 'John')
            ->set('data.l_name', 'Doe')
            ->call('nextStep')
            ->set('data.std_number', '')
            ->call('submit')
            ->assertHasNoErrors()
            ->assertRedirect('/app');

        $user->refresh();
        $this->assertNull($user->std_number);
    }

    /** @test */
    public function form_submission_sets_profile_complete(): void
    {
        $user = $this->makeUser('student', [
            'is_profile_complete' => false,
            'f_name' => null,
            'l_name' => null,
            'm_name' => null,
            'std_number' => null,
        ]);
        $this->actingAs($user);

        Livewire::test(CompleteProfile::class)
            ->set('data.f_name', 'Jane')
            ->set('data.m_name', 'Marie')
            ->set('data.l_name', 'Doe')
            ->call('nextStep')
            ->set('data.std_number', '2022-11111')
            ->call('submit')
            ->assertHasNoErrors()
            ->assertRedirect('/app');

        $user->refresh();
        $this->assertTrue($user->is_profile_complete);
        $this->assertEquals('Jane', $user->f_name);
        $this->assertEquals('Marie', $user->m_name);
        $this->assertEquals('Doe', $user->l_name);
        $this->assertEquals('Jane Marie Doe', $user->name);
        $this->assertEquals('2022-11111', $user->std_number);
    }

    /** @test */
    public function name_parsed_from_google_data(): void
    {
        // User with f_name/l_name from Google — pre-filled directly
        $user = $this->makeUser('student', [
            'is_profile_complete' => false,
            'f_name' => 'GoogleFirst',
            'l_name' => 'GoogleLast',
            'm_name' => null,
            'name' => 'Some Other Name',
        ]);
        $this->actingAs($user);

        Livewire::test(CompleteProfile::class)
            ->assertSet('data.f_name', 'GoogleFirst')
            ->assertSet('data.l_name', 'GoogleLast')
            ->assertSet('data.m_name', '');

        // User with only name — parsed into parts
        $user2 = $this->makeUser('student', [
            'is_profile_complete' => false,
            'f_name' => null,
            'l_name' => null,
            'm_name' => null,
            'name' => 'John Paul Doe',
        ]);
        $this->actingAs($user2);

        Livewire::test(CompleteProfile::class)
            ->assertSet('data.f_name', 'John')
            ->assertSet('data.m_name', 'Paul')
            ->assertSet('data.l_name', 'Doe');
    }

    /** @test */
    public function logout_button_works(): void
    {
        $user = $this->makeUser('student', ['is_profile_complete' => false]);
        $this->actingAs($user);

        Livewire::test(CompleteProfile::class)
            ->call('logout')
            ->assertRedirect('/app/login');

        $this->assertGuest('web');
    }
}
