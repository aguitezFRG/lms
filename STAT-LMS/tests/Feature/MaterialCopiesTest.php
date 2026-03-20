<?php

namespace Tests\Feature;

use App\Models\MaterialAccessEvents;
use App\Models\RrMaterialParents;
use App\Models\RrMaterials;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Feature: Material Copies (RrMaterials)
 *
 * Covers:
 * - Create digital and physical copies
 * - File upload validation (PDF only, ≤ 10 MB)
 * - Access-level filtering inherited from parent
 * - Request/Borrow action creates MaterialAccessEvent
 * - Soft delete and restore
 * - Document stream route — authorized vs denied
 * - Format (digital/physical) and availability filters
 */
class MaterialCopiesTest extends TestCase
{
    use RefreshDatabase;

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function makeParent(int $accessLevel = 1): RrMaterialParents
    {
        return $this->makeMaterialParent([
            'access_level'     => $accessLevel,
            'material_type'    => 1,
            'title'            => "Parent L{$accessLevel}",
            'author'           => 'Author Name',
            'publication_date' => now()->subYear(),
            'keywords'         => json_encode(['stats']),
            'sdgs'             => json_encode(['Quality Education']),
            'adviser'          => json_encode(['Adviser Name']),
        ]);
    }

    private function makeCopy(RrMaterialParents $parent, bool $digital = true, bool $available = true): RrMaterials
    {
        return $this->makeMaterialCopy([
            'material_parent_id' => $parent->id,
            'is_digital'         => $digital,
            'is_available'       => $available,
            'file_name'          => $digital ? 'repository/access_level_1/book_test-uuid-v1.pdf' : null,
        ]);
    }

    // ── Create ────────────────────────────────────────────────────────────────

    /** @test */
    public function committee_member_can_create_a_physical_copy(): void
    {
        Storage::fake('local');
        $parent    = $this->makeParent();
        $committee = $this->makeUser('committee');
        $this->actingAs($committee);

        Livewire::test(\App\Filament\Resources\RrMaterials\Pages\CreateRrMaterials::class)
            ->fillForm([
                'material_parent_id' => $parent->id,
                'is_digital'         => false,
                'is_available'       => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('rr_materials', [
            'material_parent_id' => $parent->id,
            'is_digital'         => false,
        ]);
    }

    /** @test */
    public function creating_digital_copy_requires_a_pdf_file(): void
    {
        $parent    = $this->makeParent();
        $committee = $this->makeUser('committee');
        $this->actingAs($committee);

        Livewire::test(\App\Filament\Resources\RrMaterials\Pages\CreateRrMaterials::class)
            ->fillForm([
                'material_parent_id' => $parent->id,
                'is_digital'         => true,
                'is_available'       => true,
                'file_name'          => null, // no file uploaded
            ])
            ->call('create')
            ->assertHasFormErrors(['file_name']);
    }

    /** @test */
    public function non_pdf_file_upload_is_rejected(): void
    {
        Storage::fake('local');
        $parent    = $this->makeParent();
        $committee = $this->makeUser('committee');
        $this->actingAs($committee);

        $wordFile = UploadedFile::fake()->create('document.docx', 100, 'application/msword');

        Livewire::test(\App\Filament\Resources\RrMaterials\Pages\CreateRrMaterials::class)
            ->fillForm([
                'material_parent_id' => $parent->id,
                'is_digital'         => true,
                'is_available'       => true,
                'file_name'          => $wordFile,
            ])
            ->call('create')
            ->assertHasFormErrors(['file_name']);
    }

    /** @test */
    public function pdf_file_larger_than_10mb_is_rejected(): void
    {
        Storage::fake('local');
        $parent    = $this->makeParent();
        $committee = $this->makeUser('committee');
        $this->actingAs($committee);

        $hugeFile = UploadedFile::fake()->create('big.pdf', 11_000, 'application/pdf'); // 11 MB

        Livewire::test(\App\Filament\Resources\RrMaterials\Pages\CreateRrMaterials::class)
            ->fillForm([
                'material_parent_id' => $parent->id,
                'is_digital'         => true,
                'is_available'       => true,
                'file_name'          => $hugeFile,
            ])
            ->call('create')
            ->assertHasFormErrors(['file_name']);
    }

    // ── Listing & Filtering ───────────────────────────────────────────────────

    /** @test */
    public function listing_shows_copies_of_accessible_parents_only(): void
    {
        $publicParent       = $this->makeParent(1);
        $confidentialParent = $this->makeParent(3);

        $this->makeCopy($publicParent, digital: false);
        $this->makeCopy($confidentialParent, digital: false);

        $student = $this->makeUser('student');
        $this->actingAs($student);

        Livewire::test(\App\Filament\Resources\RrMaterials\Pages\ListRrMaterials::class)
            ->assertSee($publicParent->title)
            ->assertDontSee($confidentialParent->title);
    }

    /** @test */
    public function digital_format_filter_returns_only_digital_copies(): void
    {
        $parent  = $this->makeParent();
        $digital  = $this->makeCopy($parent, digital: true);
        $physical = $this->makeCopy($parent, digital: false);

        $committee = $this->makeUser('committee');
        $this->actingAs($committee);

        Livewire::test(\App\Filament\Resources\RrMaterials\Pages\ListRrMaterials::class)
            ->filterTable('is_digital', true)
            ->assertSee($digital->id)
            ->assertDontSee($physical->id);
    }

    /** @test */
    public function availability_filter_returns_only_available_copies(): void
    {
        $parent      = $this->makeParent();
        $available   = $this->makeCopy($parent, available: true);
        $unavailable = $this->makeCopy($parent, available: false);

        $committee = $this->makeUser('committee');
        $this->actingAs($committee);

        Livewire::test(\App\Filament\Resources\RrMaterials\Pages\ListRrMaterials::class)
            ->filterTable('is_available', true)
            ->assertSee($available->id)
            ->assertDontSee($unavailable->id);
    }

    // ── Request / Borrow Action ───────────────────────────────────────────────

    /** @test */
    public function student_can_submit_a_borrow_request_for_physical_copy(): void
    {
        $parent  = $this->makeParent(1);
        $copy    = $this->makeCopy($parent, digital: false);
        $student = $this->makeUser('student');
        $this->actingAs($student);

        Livewire::test(\App\Filament\Resources\RrMaterials\Pages\ListRrMaterials::class)
            ->callTableAction('requestCopy', $copy);

        $this->assertDatabaseHas('material_access_events', [
            'user_id'        => $student->id,
            'rr_material_id' => $copy->id,
            'event_type'     => 'borrow',
            'status'         => 'pending',
        ]);
    }

    /** @test */
    public function student_can_submit_a_request_for_digital_copy(): void
    {
        $parent  = $this->makeParent(1);
        $copy    = $this->makeCopy($parent, digital: true);
        $student = $this->makeUser('student');
        $this->actingAs($student);

        Livewire::test(\App\Filament\Resources\RrMaterials\Pages\ListRrMaterials::class)
            ->callTableAction('requestCopy', $copy);

        $this->assertDatabaseHas('material_access_events', [
            'user_id'        => $student->id,
            'rr_material_id' => $copy->id,
            'event_type'     => 'request',
            'status'         => 'pending',
        ]);
    }

    // ── Soft Delete & Restore ─────────────────────────────────────────────────

    /** @test */
    public function committee_member_can_soft_delete_a_copy(): void
    {
        $parent    = $this->makeParent();
        $copy      = $this->makeCopy($parent);
        $committee = $this->makeUser('committee');
        $this->actingAs($committee);

        Livewire::test(\App\Filament\Resources\RrMaterials\Pages\ListRrMaterials::class)
            ->callTableAction('delete', $copy);

        $this->assertSoftDeleted('rr_materials', ['id' => $copy->id]);
    }

    /** @test */
    public function committee_member_can_restore_a_soft_deleted_copy(): void
    {
        $parent = $this->makeParent();
        $copy   = $this->makeCopy($parent);
        $copy->delete();

        $committee = $this->makeUser('committee');
        $this->actingAs($committee);

        Livewire::test(\App\Filament\Resources\RrMaterials\Pages\ListRrMaterials::class)
            ->filterTable('trashed', 'only')
            ->callTableAction('restore', $copy);

        $this->assertNotSoftDeleted('rr_materials', ['id' => $copy->id]);
    }

    // ── Document Stream Route ─────────────────────────────────────────────────

    /** @test */
    public function student_can_stream_public_digital_copy_with_approved_request(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put(
            'repository/access_level_1/book_test.pdf',
            file_get_contents(base_path('tests/fixtures/sample.pdf')) // provide a fixture PDF
        );

        $parent  = $this->makeParent(1);
        $copy    = $this->makeCopy($parent, digital: true);
        $copy->file_name = 'repository/access_level_1/book_test.pdf';
        $copy->save();

        $student = $this->makeUser('student');
        MaterialAccessEvents::factory()->create([
            'user_id'        => $student->id,
            'rr_material_id' => $copy->id,
            'event_type'     => 'request',
            'status'         => 'approved',
        ]);

        $this->actingAs($student)
            ->get(route('materials.stream', $copy))
            ->assertOk();
    }

    /** @test */
    public function student_cannot_stream_restricted_material(): void
    {
        $parent = $this->makeParent(2); // Restricted — faculty/above only
        $copy   = $this->makeCopy($parent, digital: true);

        $student = $this->makeUser('student');

        $this->actingAs($student)
            ->get(route('materials.stream', $copy))
            ->assertForbidden();
    }

    /** @test */
    public function unauthenticated_user_cannot_stream_any_material(): void
    {
        $parent = $this->makeParent(1);
        $copy   = $this->makeCopy($parent, digital: true);

        $this->get(route('materials.stream', $copy))
            ->assertRedirect('/admin/login');
    }

    /** @test */
    public function stream_returns_404_when_file_is_missing_from_disk(): void
    {
        Storage::fake('local'); // empty disk — file does not exist

        $parent = $this->makeParent(1);
        $copy   = $this->makeCopy($parent, digital: true);

        $committee = $this->makeUser('committee');

        $this->actingAs($committee)
            ->get(route('materials.stream', $copy))
            ->assertNotFound();
    }
}