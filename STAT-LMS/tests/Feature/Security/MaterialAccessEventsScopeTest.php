<?php

namespace Tests\Feature\Security;

use App\Filament\Resources\MaterialAccessEvents\MaterialAccessEventsResource;
use App\Models\MaterialAccessEvents;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Security: MaterialAccessEventsResource Query Scoping
 *
 * Verifies that MaterialAccessEventsResource::getEloquentQuery() correctly
 * restricts RR staff visibility to only level-1 physical materials:
 * - RR staff CANNOT see events for level-3 materials
 * - RR staff CAN see events for level-1 physical materials
 * - Committee CAN see all events regardless of level
 * - IT and other roles get the full query (if applicable)
 */
class MaterialAccessEventsScopeTest extends TestCase
{
    use RefreshDatabase;

    private function makeMaterialWithAccessLevel(int $accessLevel = 1, bool $isDigital = false): array
    {
        $parent = $this->makeMaterialParent([
            'access_level' => $accessLevel,
            'material_type' => 1,
            'author' => 'Author',
            'publication_date' => now()->subYear(),
            'keywords' => json_encode(['stats']),
            'sdgs' => json_encode(['Education']),
            'adviser' => json_encode(['Adviser']),
        ]);

        $copy = $this->makeMaterialCopy([
            'material_parent_id' => $parent->id,
            'is_digital' => $isDigital,
            'is_available' => true,
            'file_name' => $isDigital ? 'repo/file.pdf' : null,
        ]);

        return [$parent, $copy];
    }

    /** @test */
    public function rr_staff_cannot_see_events_for_level3_materials(): void
    {
        $rrStaff = $this->makeUser('staff/custodian');
        [$parent3, $copy3] = $this->makeMaterialWithAccessLevel(3, false);

        // Create an event for the level-3 material
        $event = MaterialAccessEvents::create([
            'user_id' => $rrStaff->id,
            'rr_material_id' => $copy3->id,
            'event_type' => 'borrow',
            'status' => 'approved',
            'approved_at' => now(),
            'due_at' => now()->addDays(7),
        ]);

        $this->actingAs($rrStaff);

        // Query using the resource scope
        $query = MaterialAccessEventsResource::getEloquentQuery();
        $results = $query->get();

        // RR staff should NOT see this event
        $this->assertFalse($results->contains($event));
    }

    /** @test */
    public function rr_staff_can_see_events_for_level1_physical_materials(): void
    {
        $rrStaff = $this->makeUser('staff/custodian');
        [$parent1, $copy1] = $this->makeMaterialWithAccessLevel(1, false);

        // Create an event for the level-1 physical material
        $event = MaterialAccessEvents::create([
            'user_id' => $rrStaff->id,
            'rr_material_id' => $copy1->id,
            'event_type' => 'borrow',
            'status' => 'approved',
            'approved_at' => now(),
            'due_at' => now()->addDays(7),
        ]);

        $this->actingAs($rrStaff);

        // Query using the resource scope
        $query = MaterialAccessEventsResource::getEloquentQuery();
        $results = $query->get();

        // RR staff SHOULD see this event (level 1, physical)
        $this->assertTrue($results->contains($event));
    }

    /** @test */
    public function rr_staff_cannot_see_events_for_level1_digital_materials(): void
    {
        $rrStaff = $this->makeUser('staff/custodian');
        [$parent1, $copy1] = $this->makeMaterialWithAccessLevel(1, true);

        // Create an event for the level-1 digital material
        $event = MaterialAccessEvents::create([
            'user_id' => $rrStaff->id,
            'rr_material_id' => $copy1->id,
            'event_type' => 'request',
            'status' => 'approved',
            'approved_at' => now(),
            'due_at' => now()->addDays(7),
        ]);

        $this->actingAs($rrStaff);

        // Query using the resource scope
        $query = MaterialAccessEventsResource::getEloquentQuery();
        $results = $query->get();

        // RR staff should NOT see this event (digital, even though level 1)
        $this->assertFalse($results->contains($event));
    }

    /** @test */
    public function committee_can_see_all_events(): void
    {
        $committee = $this->makeUser('committee');
        [$parent1, $copy1] = $this->makeMaterialWithAccessLevel(1, false);
        [$parent3, $copy3] = $this->makeMaterialWithAccessLevel(3, false);

        $event1 = MaterialAccessEvents::create([
            'user_id' => $committee->id,
            'rr_material_id' => $copy1->id,
            'event_type' => 'borrow',
            'status' => 'approved',
            'approved_at' => now(),
            'due_at' => now()->addDays(7),
        ]);

        $event3 = MaterialAccessEvents::create([
            'user_id' => $committee->id,
            'rr_material_id' => $copy3->id,
            'event_type' => 'borrow',
            'status' => 'approved',
            'approved_at' => now(),
            'due_at' => now()->addDays(7),
        ]);

        $this->actingAs($committee);

        // Query using the resource scope
        $query = MaterialAccessEventsResource::getEloquentQuery();
        $results = $query->get();

        // Committee should see both events (all levels, digital and physical)
        $this->assertTrue($results->contains($event1));
        $this->assertTrue($results->contains($event3));
    }

    /** @test */
    public function it_admin_can_see_all_events(): void
    {
        $itAdmin = $this->makeUser('it');
        [$parent1, $copy1] = $this->makeMaterialWithAccessLevel(1, false);
        [$parent3, $copy3] = $this->makeMaterialWithAccessLevel(3, false);

        $event1 = MaterialAccessEvents::create([
            'user_id' => $itAdmin->id,
            'rr_material_id' => $copy1->id,
            'event_type' => 'borrow',
            'status' => 'approved',
            'approved_at' => now(),
            'due_at' => now()->addDays(7),
        ]);

        $event3 = MaterialAccessEvents::create([
            'user_id' => $itAdmin->id,
            'rr_material_id' => $copy3->id,
            'event_type' => 'borrow',
            'status' => 'approved',
            'approved_at' => now(),
            'due_at' => now()->addDays(7),
        ]);

        $this->actingAs($itAdmin);

        // Query using the resource scope
        $query = MaterialAccessEventsResource::getEloquentQuery();
        $results = $query->get();

        // IT admin should see both events (not RR staff)
        $this->assertTrue($results->contains($event1));
        $this->assertTrue($results->contains($event3));
    }
}
