<?php

namespace Tests\Feature;

use App\Models\RrMaterialParents;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Feature: Material Catalog (RrMaterialParents)
 *
 * Covers:
 * - Access-level visibility filtering per user role
 *   (Student=1, Faculty/RR=2, Committee/IT=3)
 * - Create, view, edit, soft-delete, and restore records
 * - Policy enforcement (who may create/update/delete)
 * - Table search and material_type filter
 * - Infolist display of adviser/keyword badges
 * - Soft-delete visibility via TrashedFilter
 */
class MaterialCatalogTest extends TestCase
{
    use RefreshDatabase;

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function makeMaterial(int $accessLevel, array $overrides = []): RrMaterialParents
    {
        return RrMaterialParents::factory()->create(array_merge([
            'access_level'     => $accessLevel,
            'material_type'    => 1,
            'title'            => "Test Material L{$accessLevel}",
            'author'           => 'Test Author',
            'publication_date' => now()->subYear(),
            'keywords'         => json_encode(['stats', 'research']),
            'sdgs'             => json_encode(['Quality Education']),
            'adviser'          => json_encode(['Dr. Adviser']),
        ], $overrides));
    }

    // ── Access-Level Visibility ───────────────────────────────────────────────

    /** @test */
    public function student_sees_only_public_materials(): void
    {
        $public       = $this->makeMaterial(1, ['title' => 'Public Paper']);
        $restricted   = $this->makeMaterial(2, ['title' => 'Restricted Paper']);
        $confidential = $this->makeMaterial(3, ['title' => 'Confidential Paper']);

        $student = $this->makeUser('student');
        $this->actingAs($student);

        Livewire::test(\App\Filament\Resources\User\Catalogs\Pages\ListCatalogs::class)
            ->assertSee('Public Paper')
            ->assertDontSee('Restricted Paper')
            ->assertDontSee('Confidential Paper');
    }

    /** @test */
    public function faculty_sees_public_and_restricted_materials(): void
    {
        $public     = $this->makeMaterial(1, ['title' => 'Public Paper']);
        $restricted = $this->makeMaterial(2, ['title' => 'Restricted Paper']);
        $confidential = $this->makeMaterial(3, ['title' => 'Confidential Paper']);

        $faculty = $this->makeUser('faculty');
        $this->actingAs($faculty);

        Livewire::test(\App\Filament\Resources\User\Catalogs\Pages\ListCatalogs::class)
            ->assertSee('Public Paper')
            ->assertSee('Restricted Paper')
            ->assertDontSee('Confidential Paper');
    }

    /** @test */
    public function committee_member_sees_all_access_levels(): void
    {
        $this->makeMaterial(1, ['title' => 'Public Paper']);
        $this->makeMaterial(2, ['title' => 'Restricted Paper']);
        $this->makeMaterial(3, ['title' => 'Confidential Paper']);

        $committee = $this->makeUser('committee');
        $this->actingAs($committee);

        Livewire::test(\App\Filament\Resources\RrMaterialParents\Pages\ListRrMaterialParents::class)
            ->assertSee('Public Paper')
            ->assertSee('Restricted Paper')
            ->assertSee('Confidential Paper');
    }

    /** @test */
    public function staff_custodian_sees_public_and_restricted_in_admin_panel(): void
    {
        $this->makeMaterial(1, ['title' => 'Public Paper']);
        $this->makeMaterial(2, ['title' => 'Restricted Paper']);
        $this->makeMaterial(3, ['title' => 'Confidential Paper']);

        $staff = $this->makeUser('staff/custodian');
        $this->actingAs($staff);

        Livewire::test(\App\Filament\Resources\RrMaterialParents\Pages\ListRrMaterialParents::class)
            ->assertSee('Public Paper')
            ->assertSee('Restricted Paper')
            ->assertDontSee('Confidential Paper');
    }

    // ── Create ────────────────────────────────────────────────────────────────

    /** @test */
    public function committee_member_can_create_material(): void
    {
        $committee = $this->makeUser('committee');
        $this->actingAs($committee);

        Livewire::test(\App\Filament\Resources\RrMaterialParents\Pages\CreateRrMaterialParents::class)
            ->fillForm([
                'title'            => 'New Statistical Journal',
                'material_type'    => 3,
                'access_level'     => 1,
                'author'           => 'Dr. Santos',
                'adviser'          => ['Dr. Reyes'],
                'keywords'         => ['regression', 'ANOVA'],
                'sdgs'             => ['Quality Education'],
                'publication_date' => '2024-01-15',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('rr_material_parents', [
            'title'  => 'New Statistical Journal',
            'author' => 'Dr. Santos',
        ]);
    }

    /** @test */
    public function student_cannot_access_create_material_page(): void
    {
        $student = $this->makeUser('student');

        $this->actingAs($student)
            ->get('/admin/rr-material-parents/create')
            ->assertForbidden();
    }

    /** @test */
    public function creating_material_requires_title_and_author(): void
    {
        $committee = $this->makeUser('committee');
        $this->actingAs($committee);

        Livewire::test(\App\Filament\Resources\RrMaterialParents\Pages\CreateRrMaterialParents::class)
            ->fillForm([
                'title'         => '',
                'material_type' => 1,
                'access_level'  => 1,
                'author'        => '',
            ])
            ->call('create')
            ->assertHasFormErrors(['title', 'author']);
    }

    // ── View ──────────────────────────────────────────────────────────────────

    /** @test */
    public function committee_can_view_material_infolist(): void
    {
        $material  = $this->makeMaterial(1, ['title' => 'Viewable Material']);
        $committee = $this->makeUser('committee');
        $this->actingAs($committee);

        Livewire::test(
            \App\Filament\Resources\RrMaterialParents\Pages\ViewRrMaterialParents::class,
            ['record' => $material->id]
        )
            ->assertSee('Viewable Material');
    }

    /** @test */
    public function student_cannot_view_confidential_material(): void
    {
        $material = $this->makeMaterial(3, ['title' => 'Secret Thesis']);
        $student  = $this->makeUser('student');

        $this->actingAs($student)
            ->get("/app/rr-material-parents/{$material->id}")
            ->assertStatus(404);
    }

    // ── Edit ──────────────────────────────────────────────────────────────────

    /** @test */
    public function it_admin_can_edit_material_title(): void
    {
        $material = $this->makeMaterial(1, ['title' => 'Original Title']);
        $it       = $this->makeUser('it');
        $this->actingAs($it);

        Livewire::test(
            \App\Filament\Resources\RrMaterialParents\Pages\EditRrMaterialParents::class,
            ['record' => $material->id]
        )
            ->fillForm(['title' => 'Updated Title'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('rr_material_parents', ['title' => 'Updated Title']);
    }

    /** @test */
    public function faculty_user_cannot_edit_another_authors_material(): void
    {
        $material = $this->makeMaterial(2, ['title' => 'Faculty Material']);
        $faculty  = $this->makeUser('faculty');

        // Faculty member who is NOT the author has no update permission
        $this->actingAs($faculty)
            ->get("/admin/rr-material-parents/{$material->id}/edit")
            ->assertForbidden();
    }

    // ── Soft Delete & Restore ─────────────────────────────────────────────────

    /** @test */
    public function committee_member_can_soft_delete_material(): void
    {
        $material  = $this->makeMaterial(1, ['title' => 'Deletable Material']);
        $committee = $this->makeUser('committee');
        $this->actingAs($committee);

        Livewire::test(
            \App\Filament\Resources\RrMaterialParents\Pages\EditRrMaterialParents::class,
            ['record' => $material->id]
        )
            ->callAction('delete');

        $this->assertSoftDeleted('rr_material_parents', ['id' => $material->id]);
    }

    /** @test */
    public function committee_member_can_restore_soft_deleted_material(): void
    {
        $material = $this->makeMaterial(1);
        $material->delete();
        $this->assertSoftDeleted('rr_material_parents', ['id' => $material->id]);

        $committee = $this->makeUser('committee');
        $this->actingAs($committee);

        Livewire::test(
            \App\Filament\Resources\RrMaterialParents\Pages\EditRrMaterialParents::class,
            ['record' => $material->id]
        )
            ->callAction('restore');

        $this->assertNotSoftDeleted('rr_material_parents', ['id' => $material->id]);
    }

    /** @test */
    public function soft_deleted_materials_are_hidden_by_default_in_listing(): void
    {
        $active  = $this->makeMaterial(1, ['title' => 'Active Material']);
        $deleted = $this->makeMaterial(1, ['title' => 'Deleted Material']);
        $deleted->delete();

        $committee = $this->makeUser('committee');
        $this->actingAs($committee);

        Livewire::test(\App\Filament\Resources\RrMaterialParents\Pages\ListRrMaterialParents::class)
            ->assertSee('Active Material')
            ->assertDontSee('Deleted Material');
    }

    /** @test */
    public function trashed_filter_reveals_soft_deleted_materials(): void
    {
        $deleted = $this->makeMaterial(1, ['title' => 'Deleted Material']);
        $deleted->delete();

        $committee = $this->makeUser('committee');
        $this->actingAs($committee);

        Livewire::test(\App\Filament\Resources\RrMaterialParents\Pages\ListRrMaterialParents::class)
            ->filterTable('trashed', 'with')
            ->assertSee('Deleted Material');
    }

    // ── Table Filters & Search ────────────────────────────────────────────────

    /** @test */
    public function material_type_filter_narrows_results(): void
    {
        $this->makeMaterial(1, ['title' => 'Book Title',   'material_type' => 1]);
        $this->makeMaterial(1, ['title' => 'Thesis Title', 'material_type' => 2]);

        $committee = $this->makeUser('committee');
        $this->actingAs($committee);

        Livewire::test(\App\Filament\Resources\RrMaterialParents\Pages\ListRrMaterialParents::class)
            ->filterTable('material_type', [1])
            ->assertSee('Book Title')
            ->assertDontSee('Thesis Title');
    }

    /** @test */
    public function table_search_finds_material_by_title(): void
    {
        $this->makeMaterial(1, ['title' => 'Unique Bayesian Study']);
        $this->makeMaterial(1, ['title' => 'Unrelated Paper']);

        $committee = $this->makeUser('committee');
        $this->actingAs($committee);

        Livewire::test(\App\Filament\Resources\RrMaterialParents\Pages\ListRrMaterialParents::class)
            ->searchTable('Bayesian')
            ->assertSee('Unique Bayesian Study')
            ->assertDontSee('Unrelated Paper');
    }
}