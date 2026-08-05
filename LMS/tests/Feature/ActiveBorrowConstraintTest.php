<?php

namespace Tests\Feature;

use App\Models\MaterialAccessEvents;
use App\Models\RrMaterials;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActiveBorrowConstraintTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_one_active_approved_borrow_can_exist_for_a_copy(): void
    {
        $copy = RrMaterials::factory()->create();

        MaterialAccessEvents::factory()->create([
            'user_id' => User::factory(),
            'rr_material_id' => $copy->getKey(),
            'event_type' => 'borrow',
            'status' => 'approved',
            'returned_at' => null,
        ]);

        $this->expectException(QueryException::class);

        MaterialAccessEvents::factory()->create([
            'user_id' => User::factory(),
            'rr_material_id' => $copy->getKey(),
            'event_type' => 'borrow',
            'status' => 'approved',
            'returned_at' => null,
        ]);
    }

    public function test_pending_requests_for_the_same_copy_remain_allowed(): void
    {
        $copy = RrMaterials::factory()->create();

        MaterialAccessEvents::factory()->count(2)->create([
            'rr_material_id' => $copy->getKey(),
            'event_type' => 'borrow',
            'status' => 'pending',
            'returned_at' => null,
        ]);

        $this->assertSame(
            2,
            MaterialAccessEvents::query()
                ->where('rr_material_id', $copy->getKey())
                ->where('status', 'pending')
                ->count(),
        );
    }

    public function test_a_new_borrow_is_allowed_after_the_previous_copy_is_returned(): void
    {
        $copy = RrMaterials::factory()->create();

        MaterialAccessEvents::factory()->create([
            'rr_material_id' => $copy->getKey(),
            'event_type' => 'borrow',
            'status' => 'approved',
            'returned_at' => now()->subDay(),
        ]);

        MaterialAccessEvents::factory()->create([
            'rr_material_id' => $copy->getKey(),
            'event_type' => 'borrow',
            'status' => 'approved',
            'returned_at' => null,
        ]);

        $this->assertSame(
            2,
            MaterialAccessEvents::query()
                ->where('rr_material_id', $copy->getKey())
                ->where('event_type', 'borrow')
                ->count(),
        );
    }
}
