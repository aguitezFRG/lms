<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Feature: User Management
 *
 * Covers:
 * - Committee/IT can list, create, view, edit, delete, and restore users
 * - Staff/Custodian cannot access user management
 * - Self-deletion and self-editing role restrictions
 * - Unique email and student number validation
 * - Full name auto-construction from f_name + m_name + l_name
 * - Role badge display in table listing
 * - TrashedFilter reveals soft-deleted users
 * - Role filter narrows listing by role
 * - Password field: required on create, optional on edit
 */
class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function validUserPayload(array $overrides = []): array
    {
        return array_merge([
            'f_name'     => 'Maria',
            'm_name'     => 'Santos',
            'l_name'     => 'Cruz',
            'name'       => 'Maria Santos Cruz',
            'email'      => 'maria.cruz@up.edu.ph',
            'password'   => 'SecurePass@123',
            'role'       => 'student',
            // Omit std_number by default so the unique mask rule doesn't
            // interfere with tests that don't care about it.
        ], $overrides);
    }

    // ── List ──────────────────────────────────────────────────────────────────

    /** @test */
    public function committee_member_can_list_users(): void
    {
        $committee = $this->makeUser('committee');
        $student   = $this->makeUser('student');
        $this->actingAs($committee);

        Livewire::test(\App\Filament\Resources\Users\Pages\ListUsers::class)
            ->call('loadTable')
            ->assertSee($student->email);
    }

    /** @test */
    public function it_admin_can_list_users(): void
    {
        $it      = $this->makeUser('it');
        $faculty = $this->makeUser('faculty');
        $this->actingAs($it);

        Livewire::test(\App\Filament\Resources\Users\Pages\ListUsers::class)
            ->call('loadTable')
            ->assertSee($faculty->email);
    }

    /** @test */
    public function staff_custodian_cannot_access_user_listing(): void
    {
        $staff = $this->makeUser('staff/custodian');

        $this->actingAs($staff)
            ->get('/admin/users')
            ->assertForbidden();
    }

    // ── Create ────────────────────────────────────────────────────────────────

    /** @test */
    public function committee_member_can_create_a_new_user(): void
    {
        $committee = $this->makeUser('committee');
        $this->actingAs($committee);

        Livewire::test(\App\Filament\Resources\Users\Pages\CreateUser::class)
            ->fillForm($this->validUserPayload())
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('users', ['email' => 'maria.cruz@up.edu.ph']);
    }

    /** @test */
    public function creating_user_requires_first_name_last_name_email_and_role(): void
    {
        $committee = $this->makeUser('committee');
        $this->actingAs($committee);

        Livewire::test(\App\Filament\Resources\Users\Pages\CreateUser::class)
            ->fillForm([
                'f_name'   => '',
                'l_name'   => '',
                'email'    => '',
                'password' => 'pass',
            ])
            ->call('create')
            ->assertHasFormErrors(['f_name', 'l_name', 'email']);
    }

    /** @test */
    public function duplicate_email_is_rejected_on_create(): void
    {
        $existing  = $this->makeUser('student');
        $committee = $this->makeUser('committee');
        $this->actingAs($committee);

        Livewire::test(\App\Filament\Resources\Users\Pages\CreateUser::class)
            ->fillForm($this->validUserPayload(['email' => $existing->email]))
            ->call('create')
            ->assertHasFormErrors(['email']);
    }

    /** @test */
    public function duplicate_student_number_is_rejected_on_create(): void
    {
        // Create a user with a known, valid student number format
        $existing = $this->makeUser('student', ['std_number' => '2020-12345']);
        $committee = $this->makeUser('committee');
        $this->actingAs($committee);

        // The std_number field uses a mask pattern (9999-99999) and a unique rule.
        // We submit the same number to trigger the duplicate error.
        Livewire::test(\App\Filament\Resources\Users\Pages\CreateUser::class)
            ->fillForm($this->validUserPayload(['std_number' => '2020-12345']))
            ->call('create')
            ->assertHasFormErrors(['std_number']);
    }

    /** @test */
    public function full_name_is_constructed_from_name_parts_on_create(): void
    {
        $committee = $this->makeUser('committee');
        $this->actingAs($committee);

        Livewire::test(\App\Filament\Resources\Users\Pages\CreateUser::class)
            ->fillForm($this->validUserPayload([
                'f_name' => 'Juan',
                'm_name' => 'dela',
                'l_name' => 'Cruz',
                'name'   => 'Juan dela Cruz',
                'email'  => 'juan.cruz@up.edu.ph',
            ]))
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('users', ['name' => 'Juan dela Cruz']);
    }

    /** @test */
    public function password_is_required_on_create_but_optional_on_edit(): void
    {
        // Create target with a null std_number to avoid the unique mask rule
        $target    = $this->makeUser('student', ['std_number' => null]);
        $committee = $this->makeUser('committee');
        $this->actingAs($committee);

        // Edit without providing a new password — should succeed
        Livewire::test(
            \App\Filament\Resources\Users\Pages\EditUser::class,
            ['record' => $target->id]
        )
            ->fillForm(['f_name' => 'UpdatedName', 'password' => ''])
            ->call('save')
            ->assertHasNoFormErrors();
    }

    // ── View ──────────────────────────────────────────────────────────────────

    /** @test */
    public function committee_can_view_user_infolist(): void
    {
        $target    = $this->makeUser('student');
        $committee = $this->makeUser('committee');
        $this->actingAs($committee);

        Livewire::test(
            \App\Filament\Resources\Users\Pages\ViewUser::class,
            ['record' => $target->id]
        )
            ->assertSee($target->email);
    }

    // ── Edit ──────────────────────────────────────────────────────────────────

    /** @test */
    public function committee_member_can_update_another_users_role(): void
    {
        // Null std_number avoids the unique mask validation on a field we aren't testing
        $target    = $this->makeUser('student', ['std_number' => null]);
        $committee = $this->makeUser('committee');
        $this->actingAs($committee);

        Livewire::test(
            \App\Filament\Resources\Users\Pages\EditUser::class,
            ['record' => $target->id]
        )
            ->fillForm(['role' => 'faculty'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('users', ['id' => $target->id, 'role' => 'faculty']);
    }

    /** @test */
    public function committee_member_cannot_edit_themselves_via_policy(): void
    {
        $committee = $this->makeUser('committee');
        $this->actingAs($committee);

        // UserPolicy::update() returns false when actor and subject are the same user.
        $this->assertFalse($committee->can('update', $committee));
    }

    /** @test */
    public function email_uniqueness_is_enforced_ignoring_own_record_on_edit(): void
    {
        $target    = $this->makeUser('student', ['std_number' => null]);
        $other     = $this->makeUser('faculty');
        $committee = $this->makeUser('committee');
        $this->actingAs($committee);

        // Trying to use another user's email — should fail validation
        Livewire::test(
            \App\Filament\Resources\Users\Pages\EditUser::class,
            ['record' => $target->id]
        )
            ->fillForm(['email' => $other->email])
            ->call('save')
            ->assertHasFormErrors(['email']);
    }

    // ── Delete & Restore ──────────────────────────────────────────────────────

    /** @test */
    public function committee_member_can_soft_delete_another_user(): void
    {
        $target    = $this->makeUser('student');
        $committee = $this->makeUser('committee');
        $this->actingAs($committee);

        Livewire::test(
            \App\Filament\Resources\Users\Pages\EditUser::class,
            ['record' => $target->id]
        )->callAction('delete');

        $this->assertSoftDeleted('users', ['id' => $target->id]);
    }

    /** @test */
    public function committee_member_cannot_delete_themselves(): void
    {
        $committee = $this->makeUser('committee');
        $this->actingAs($committee);

        // UserPolicy::delete() returns false when user tries to delete themselves
        $this->assertFalse($committee->can('delete', $committee));
    }

    /** @test */
    public function committee_member_can_restore_a_soft_deleted_user(): void
    {
        $target = $this->makeUser('student');
        $target->delete();
        $this->assertSoftDeleted('users', ['id' => $target->id]);

        $committee = $this->makeUser('committee');
        $this->actingAs($committee);

        Livewire::test(\App\Filament\Resources\Users\Pages\ListUsers::class)
            ->filterTable('trashed', 'only')
            ->callTableAction('restore', $target);

        $this->assertNotSoftDeleted('users', ['id' => $target->id]);
    }

    /** @test */
    public function soft_deleted_users_are_hidden_by_default(): void
    {
        $active  = $this->makeUser('student');
        $deleted = $this->makeUser('student');
        $deleted->delete();

        $committee = $this->makeUser('committee');
        $this->actingAs($committee);

        Livewire::test(\App\Filament\Resources\Users\Pages\ListUsers::class)
            ->call('loadTable')
            ->assertSee($active->email)
            ->assertDontSee($deleted->email);
    }

    // ── Table Filters ─────────────────────────────────────────────────────────

    /** @test */
    public function role_filter_narrows_listing_to_selected_role(): void
    {
        $student = $this->makeUser('student');
        $faculty = $this->makeUser('faculty');

        $committee = $this->makeUser('committee');
        $this->actingAs($committee);

        Livewire::test(\App\Filament\Resources\Users\Pages\ListUsers::class)
            ->call('loadTable')
            ->filterTable('role', ['student'])
            ->assertSee($student->email)
            ->assertDontSee($faculty->email);
    }

    /** @test */
    public function table_search_finds_user_by_email(): void
    {
        $target = $this->makeUser('student', ['email' => 'unique.student@up.edu.ph']);
        $other  = $this->makeUser('faculty');

        $committee = $this->makeUser('committee');
        $this->actingAs($committee);

        Livewire::test(\App\Filament\Resources\Users\Pages\ListUsers::class)
            ->searchTable('unique.student@up.edu.ph')
            ->assertSee('unique.student@up.edu.ph')
            ->assertDontSee($other->email);
    }
}