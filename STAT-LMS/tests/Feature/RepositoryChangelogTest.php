<?php

namespace Tests\Feature;

use App\Enums\RepositoryChangeType;
use App\Models\MaterialAccessEvents;
use App\Models\RepositoryChangeLogs;
use App\Models\RrMaterialParents;
use App\Models\RrMaterials;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Feature: Repository Change Logs (Audit Trail)
 *
 * Covers:
 * - Creating an RrMaterialParent auto-generates a CREATE log entry
 * - Updating an RrMaterialParent auto-generates an UPDATE log entry
 * - Soft-deleting auto-generates a DELETE log entry
 * - Restoring auto-generates a RESTORE log entry
 * - Same auto-logging for RrMaterials and MaterialAccessEvents
 * - User changes are also logged (with target_user_id set)
 * - Log entries are read-only — no one can delete or force-delete
 * - Only Committee and IT can view logs
 * - Staff/Custodian and students cannot access the log panel
 * - Table filter by change_type
 * - Table filter by table_changed
 * - Editor relationship is correctly resolved
 */
class RepositoryChangeLogsTest extends TestCase
{
    use RefreshDatabase;

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function makeParent(int $accessLevel = 1): RrMaterialParents
    {
        return $this->makeMaterialParent([
            'access_level'     => $accessLevel,
            'material_type'    => 1,
            'author'           => 'Observer Test Author',
            'publication_date' => now()->subYear(),
            'keywords'         => json_encode(['stats']),
            'sdgs'             => json_encode(['Education']),
            'adviser'          => json_encode(['Adviser']),
        ]);
    }

    // ── Auto-Logging: RrMaterialParents ───────────────────────────────────────

    /** @test */
    public function creating_a_material_parent_generates_a_create_log(): void
    {
        $committee = $this->makeUser('committee');
        $this->actingAs($committee);

        $parent = $this->makeParent();

        $this->assertDatabaseHas('repository_change_logs', [
            'editor_id'    => $committee->id,
            'table_changed' => 'rr_material_parents',
            'change_type'  => RepositoryChangeType::CREATE->value,
        ]);
    }

    /** @test */
    public function updating_a_material_parent_generates_an_update_log(): void
    {
        $committee = $this->makeUser('committee');
        $this->actingAs($committee);

        $parent = $this->makeParent();
        $parent->update(['title' => 'Updated Title']);

        $this->assertDatabaseHas('repository_change_logs', [
            'editor_id'    => $committee->id,
            'table_changed' => 'rr_material_parents',
            'change_type'  => RepositoryChangeType::UPDATE->value,
        ]);
    }

    /** @test */
    public function soft_deleting_a_material_parent_generates_a_delete_log(): void
    {
        $committee = $this->makeUser('committee');
        $this->actingAs($committee);

        $parent = $this->makeParent();
        $parent->delete();

        $this->assertDatabaseHas('repository_change_logs', [
            'editor_id'    => $committee->id,
            'table_changed' => 'rr_material_parents',
            'change_type'  => RepositoryChangeType::DELETE->value,
        ]);
    }

    /** @test */
    public function restoring_a_material_parent_generates_a_restore_log(): void
    {
        $committee = $this->makeUser('committee');
        $this->actingAs($committee);

        $parent = $this->makeParent();
        $parent->delete();
        $parent->restore();

        $this->assertDatabaseHas('repository_change_logs', [
            'editor_id'    => $committee->id,
            'table_changed' => 'rr_material_parents',
            'change_type'  => RepositoryChangeType::RESTORE->value,
        ]);
    }

    // ── Auto-Logging: RrMaterials ─────────────────────────────────────────────

    /** @test */
    public function creating_a_material_copy_generates_a_create_log_with_material_id(): void
    {
        $committee = $this->makeUser('committee');
        $this->actingAs($committee);

        $parent = $this->makeParent();
        $copy   = $this->makeMaterialCopy([
            'material_parent_id' => $parent->id,
            'is_digital'         => false,
            'is_available'       => true,
        ]);

        $this->assertDatabaseHas('repository_change_logs', [
            'editor_id'      => $committee->id,
            'rr_material_id' => $copy->id,
            'table_changed'  => 'rr_materials',
            'change_type'    => RepositoryChangeType::CREATE->value,
        ]);
    }

    // ── Auto-Logging: Users ───────────────────────────────────────────────────

    /** @test */
    public function editing_a_user_sets_target_user_id_in_log(): void
    {
        $committee = $this->makeUser('committee');
        $target    = $this->makeUser('student');
        $this->actingAs($committee);

        $target->update(['f_name' => 'NewName']);

        $this->assertDatabaseHas('repository_change_logs', [
            'editor_id'      => $committee->id,
            'target_user_id' => $target->id,
            'table_changed'  => 'users',
            'change_type'    => RepositoryChangeType::UPDATE->value,
        ]);
    }

    /** @test */
    public function creating_a_user_generates_a_log_with_target_user_id(): void
    {
        $committee = $this->makeUser('committee');
        $this->actingAs($committee);

        $newUser = $this->makeUser('student');

        $this->assertDatabaseHas('repository_change_logs', [
            'editor_id'      => $committee->id,
            'target_user_id' => $newUser->id,
            'table_changed'  => 'users',
            'change_type'    => RepositoryChangeType::CREATE->value,
        ]);
    }

    // ── Auto-Logging: MaterialAccessEvents ────────────────────────────────────

    /** @test */
    public function creating_an_access_event_generates_a_log(): void
    {
        $committee = $this->makeUser('committee');
        $student   = $this->makeUser('student');
        $parent    = $this->makeParent();
        $copy      = $this->makeMaterialCopy([
            'material_parent_id' => $parent->id,
            'is_digital'         => true,
            'is_available'       => true,
        ]);

        $this->actingAs($student);

        MaterialAccessEvents::create([
            'user_id'        => $student->id,
            'rr_material_id' => $copy->id,
            'event_type'     => 'request',
            'status'         => 'pending',
        ]);

        $this->assertDatabaseHas('repository_change_logs', [
            'table_changed' => 'material_access_events',
            'change_type'   => RepositoryChangeType::CREATE->value,
        ]);
    }

    // ── Policy: Read-Only ─────────────────────────────────────────────────────

    /** @test */
    public function nobody_can_delete_a_change_log_entry(): void
    {
        $committee = $this->makeUser('committee');
        $log       = RepositoryChangeLogs::factory()->create([
            'editor_id'   => $committee->id,
            'table_changed' => 'users',
            'change_type' => RepositoryChangeType::CREATE->value,
            'changed_at'  => now(),
        ]);

        // Policy::delete() returns false for all roles
        $this->assertFalse($committee->can('delete', $log));
        $this->assertFalse($committee->can('deleteAny', RepositoryChangeLogs::class));
    }

    /** @test */
    public function nobody_can_force_delete_a_change_log(): void
    {
        $committee = $this->makeUser('committee');
        $log       = RepositoryChangeLogs::factory()->create([
            'editor_id'   => $committee->id,
            'table_changed' => 'rr_materials',
            'change_type' => RepositoryChangeType::UPDATE->value,
            'changed_at'  => now(),
        ]);

        $this->assertFalse($committee->can('forceDelete', $log));
    }

    // ── Panel Visibility ──────────────────────────────────────────────────────

    /** @test */
    public function committee_member_can_view_the_change_log_listing(): void
    {
        $committee = $this->makeUser('committee');
        $this->actingAs($committee);

        Livewire::test(\App\Filament\Resources\RepositoryChangeLogs\Pages\ListRepositoryChangeLogs::class)
            ->assertSuccessful();
    }

    /** @test */
    public function it_admin_can_view_the_change_log_listing(): void
    {
        $it = $this->makeUser('it');
        $this->actingAs($it);

        Livewire::test(\App\Filament\Resources\RepositoryChangeLogs\Pages\ListRepositoryChangeLogs::class)
            ->assertSuccessful();
    }

    /** @test */
    public function staff_custodian_is_forbidden_from_change_log_listing(): void
    {
        $staff = $this->makeUser('staff/custodian');

        $this->actingAs($staff)
            ->get('/admin/repository-change-logs')
            ->assertForbidden();
    }

    /** @test */
    public function student_is_forbidden_from_change_log_listing(): void
    {
        $student = $this->makeUser('student');

        $this->actingAs($student)
            ->get('/admin/repository-change-logs')
            ->assertForbidden();
    }

    // ── Table Filters ─────────────────────────────────────────────────────────

    /** @test */
    public function change_type_filter_narrows_results(): void
    {
        $committee = $this->makeUser('committee');
        $this->actingAs($committee);

        RepositoryChangeLogs::factory()->create([
            'editor_id'    => $committee->id,
            'table_changed' => 'users',
            'change_type'  => RepositoryChangeType::CREATE->value,
            'changed_at'   => now(),
        ]);
        RepositoryChangeLogs::factory()->create([
            'editor_id'    => $committee->id,
            'table_changed' => 'users',
            'change_type'  => RepositoryChangeType::DELETE->value,
            'changed_at'   => now(),
        ]);

        Livewire::test(\App\Filament\Resources\RepositoryChangeLogs\Pages\ListRepositoryChangeLogs::class)
            ->filterTable('change_type', RepositoryChangeType::CREATE->value)
            ->assertSee(RepositoryChangeType::CREATE->getLabel())
            ->assertDontSee(RepositoryChangeType::DELETE->getLabel());
    }

    /** @test */
    public function table_changed_filter_narrows_results_by_table(): void
    {
        $committee = $this->makeUser('committee');
        $this->actingAs($committee);

        RepositoryChangeLogs::factory()->create([
            'editor_id'    => $committee->id,
            'table_changed' => 'users',
            'change_type'  => RepositoryChangeType::UPDATE->value,
            'changed_at'   => now(),
        ]);
        RepositoryChangeLogs::factory()->create([
            'editor_id'    => $committee->id,
            'table_changed' => 'rr_materials',
            'change_type'  => RepositoryChangeType::UPDATE->value,
            'changed_at'   => now(),
        ]);

        Livewire::test(\App\Filament\Resources\RepositoryChangeLogs\Pages\ListRepositoryChangeLogs::class)
            ->filterTable('table_changed', 'users')
            ->assertSee('users')
            ->assertDontSee('rr_materials');
    }
}