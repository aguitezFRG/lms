<?php

namespace Tests\Feature\Security;

use App\Models\RrMaterialParents;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

/**
 * Security: RrMaterialParentsPolicy Create Authorization
 *
 * Verifies that RrMaterialParents::create() is restricted to allowlist:
 * [SUPER_ADMIN, COMMITTEE, IT]
 *
 * Ensures:
 * - Faculty CANNOT create material parents
 * - RR staff CANNOT create material parents
 * - Student CANNOT create material parents
 * - Committee CAN create material parents
 * - IT CAN create material parents
 * - Super Admin CAN create material parents
 */
class RrMaterialParentsPolicyTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function faculty_cannot_create_material_parent(): void
    {
        $faculty = $this->makeUser('faculty');

        $this->assertFalse(
            Gate::forUser($faculty)->allows('create', RrMaterialParents::class)
        );
    }

    /** @test */
    public function rr_staff_cannot_create_material_parent(): void
    {
        $rr = $this->makeUser('staff/custodian');

        $this->assertFalse(
            Gate::forUser($rr)->allows('create', RrMaterialParents::class)
        );
    }

    /** @test */
    public function student_cannot_create_material_parent(): void
    {
        $student = $this->makeUser('student');

        $this->assertFalse(
            Gate::forUser($student)->allows('create', RrMaterialParents::class)
        );
    }

    /** @test */
    public function committee_can_create_material_parent(): void
    {
        $committee = $this->makeUser('committee');

        $this->assertTrue(
            Gate::forUser($committee)->allows('create', RrMaterialParents::class)
        );
    }

    /** @test */
    public function it_admin_can_create_material_parent(): void
    {
        $it = $this->makeUser('it');

        $this->assertTrue(
            Gate::forUser($it)->allows('create', RrMaterialParents::class)
        );
    }

    /** @test */
    public function super_admin_can_create_material_parent(): void
    {
        $superAdmin = $this->makeUser('super_admin');

        $this->assertTrue(
            Gate::forUser($superAdmin)->allows('create', RrMaterialParents::class)
        );
    }

    /** @test */
    public function staff_custodian_cannot_create_material_parent(): void
    {
        $staff = $this->makeUser('staff/custodian');

        // Staff/Custodian are not in the allowlist, should NOT be able to create
        $this->assertFalse(
            Gate::forUser($staff)->allows('create', RrMaterialParents::class)
        );
    }

    /** @test */
    public function faculty_cannot_update_material_parent(): void
    {
        $faculty = $this->makeUser('faculty');
        $material = $this->makeMaterialParent([
            'access_level' => 1,
            'material_type' => 1,
            'author' => 'Author',
            'publication_date' => now()->subYear(),
            'keywords' => json_encode(['stats']),
            'sdgs' => json_encode(['Education']),
            'adviser' => json_encode(['Adviser']),
        ]);

        $this->assertFalse(
            Gate::forUser($faculty)->allows('update', $material)
        );
    }

    /** @test */
    public function committee_can_update_material_parent(): void
    {
        $committee = $this->makeUser('committee');
        $material = $this->makeMaterialParent([
            'access_level' => 1,
            'material_type' => 1,
            'author' => 'Author',
            'publication_date' => now()->subYear(),
            'keywords' => json_encode(['stats']),
            'sdgs' => json_encode(['Education']),
            'adviser' => json_encode(['Adviser']),
        ]);

        $this->assertTrue(
            Gate::forUser($committee)->allows('update', $material)
        );
    }

    /** @test */
    public function faculty_cannot_delete_material_parent(): void
    {
        $faculty = $this->makeUser('faculty');
        $material = $this->makeMaterialParent([
            'access_level' => 1,
            'material_type' => 1,
            'author' => 'Author',
            'publication_date' => now()->subYear(),
            'keywords' => json_encode(['stats']),
            'sdgs' => json_encode(['Education']),
            'adviser' => json_encode(['Adviser']),
        ]);

        $this->assertFalse(
            Gate::forUser($faculty)->allows('delete', $material)
        );
    }

    /** @test */
    public function committee_can_delete_material_parent(): void
    {
        $committee = $this->makeUser('committee');
        $material = $this->makeMaterialParent([
            'access_level' => 1,
            'material_type' => 1,
            'author' => 'Author',
            'publication_date' => now()->subYear(),
            'keywords' => json_encode(['stats']),
            'sdgs' => json_encode(['Education']),
            'adviser' => json_encode(['Adviser']),
        ]);

        $this->assertTrue(
            Gate::forUser($committee)->allows('delete', $material)
        );
    }
}
