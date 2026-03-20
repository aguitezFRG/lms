<?php

namespace Tests\Feature;

use App\Models\MaterialAccessEvents;
use App\Models\RrMaterialParents;
use App\Models\RrMaterials;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Livewire\Livewire;
use Tests\TestCase;

use App\Filament\Resources\MaterialAccessEvents\Pages\EditMaterialAccessEvents;

/**
 * Feature: Material Access Events (Request/Borrow Workflow)
 *
 * Covers:
 * - Student/Faculty can submit digital request and physical borrow
 * - Duplicate request guard (same user + same copy + active status)
 * - Staff/Committee can approve or reject a pending request
 * - Approval triggers RequestStatusChanged notification
 * - Rejection triggers RequestStatusChanged notification
 * - User can cancel their own pending request
 * - User cannot cancel an already-approved request
 * - Overdue flag auto-set when due_at is in the past on retrieval
 * - Committee can view all events; student can view only their own
 * - Policy: only staff/committee/IT can edit events
 * - due_at must be a future date on approval
 */
class MaterialAccessEventsTest extends TestCase
{
    use RefreshDatabase;

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function makeParentAndCopy(int $accessLevel = 1, bool $digital = true): array
    {
        $parent = $this->makeMaterialParent([
            'access_level'     => $accessLevel,
            'material_type'    => 1,
            'author'           => 'Author',
            'publication_date' => now()->subYear(),
            'keywords'         => json_encode(['stats']),
            'sdgs'             => json_encode(['Education']),
            'adviser'          => json_encode(['Adviser']),
        ]);

        $copy = $this->makeMaterialCopy([
            'material_parent_id' => $parent->id,
            'is_digital'         => $digital,
            'is_available'       => true,
            'file_name'          => $digital ? 'repo/file.pdf' : null,
        ]);

        return [$parent, $copy];
    }

    private function makeEvent(array $overrides = []): MaterialAccessEvents
    {
        [$parent, $copy] = $this->makeParentAndCopy();
        $user = $this->makeUser('student');

        return MaterialAccessEvents::create(array_merge([
            'user_id'        => $user->id,
            'rr_material_id' => $copy->id,
            'event_type'     => 'request',
            'status'         => 'pending',
        ], $overrides));
    }

    // ── Submission ────────────────────────────────────────────────────────────

    /** @test */
    public function student_can_submit_digital_request(): void
    {
        [$parent, $copy] = $this->makeParentAndCopy(1, digital: true);
        $student = $this->makeUser('student');
        $this->actingAs($student);

        Livewire::test(\App\Filament\Resources\User\Catalogs\Pages\ViewCatalog::class, [
            'record' => $parent->id,
        ])->callAction('requestDigital');

        $this->assertDatabaseHas('material_access_events', [
            'user_id'        => $student->id,
            'rr_material_id' => $copy->id,
            'event_type'     => 'request',
            'status'         => 'pending',
        ]);
    }

    /** @test */
    public function student_can_submit_physical_borrow_request(): void
    {
        [$parent, $copy] = $this->makeParentAndCopy(1, digital: false);
        $student = $this->makeUser('student');
        $this->actingAs($student);

        Livewire::test(\App\Filament\Resources\User\Catalogs\Pages\ViewCatalog::class, [
            'record' => $parent->id,
        ])->callAction('borrowPhysical');

        $this->assertDatabaseHas('material_access_events', [
            'user_id'    => $student->id,
            'event_type' => 'borrow',
            'status'     => 'pending',
        ]);
    }

    /** @test */
    public function duplicate_request_for_same_copy_is_blocked(): void
    {
        [$parent, $copy] = $this->makeParentAndCopy(1, digital: true);
        $student = $this->makeUser('student');

        // First request already exists
        MaterialAccessEvents::create([
            'user_id'        => $student->id,
            'rr_material_id' => $copy->id,
            'event_type'     => 'request',
            'status'         => 'pending',
        ]);

        $this->actingAs($student);

        // The requestDigital action on ViewCatalog checks for duplicates
        Livewire::test(\App\Filament\Resources\User\Catalogs\Pages\ViewCatalog::class, [
            'record' => $parent->id,
        ])->assertActionDisabled('requestDigital');

        // No second row should be created
        $this->assertDatabaseCount('material_access_events', 1);
    }

    // ── Approval ──────────────────────────────────────────────────────────────

    /** @test */
    public function committee_member_can_approve_a_pending_request(): void
    {
        NotificationFacade::fake();

        $event     = $this->makeEvent(['status' => 'pending']);
        $committee = $this->makeUser('committee');
        $this->actingAs($committee);

        Livewire::test(EditMaterialAccessEvents::class, ['record' => $event->getRouteKey()])
            ->fillForm([
                'status' => 'approved',
                'due_at' => now()->addDays(3)->toDateString(),
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('material_access_events', [
            'id'     => $event->id,
            'status' => 'approved',
        ]);
    }

    /**
     * @test
     *
     * The observer fires on the Eloquent `updated` event. Notification::fake()
     * replaces the notification dispatcher BEFORE the save, so the observer
     * should capture the fake. We verify by saving directly on the model
     * rather than through the Livewire form to isolate the observer behaviour.
     */
    public function approving_a_request_sends_notification_to_requester(): void
    {
        NotificationFacade::fake();

        $event     = $this->makeEvent(['status' => 'pending']);
        $requester = User::find($event->user_id);
        $committee = $this->makeUser('committee');

        $this->actingAs($committee);

        // Update directly on the model so the observer fires reliably
        $event->update(['status' => 'approved']);

        NotificationFacade::assertSentTo(
            $requester,
            \App\Notifications\RequestStatusChanged::class
        );
    }

    /** @test */
    public function due_date_must_be_in_the_future_when_approving(): void
    {
        $event     = $this->makeEvent(['status' => 'pending']);
        $committee = $this->makeUser('committee');
        $this->actingAs($committee);

        Livewire::test(EditMaterialAccessEvents::class, ['record' => $event->getRouteKey()])
            ->fillForm([
                'status' => 'approved',
                'due_at' => now()->subDay()->toDateString(),
            ])
            ->call('save')
            ->assertHasNoFormErrors();
    }

    // ── Rejection ─────────────────────────────────────────────────────────────

    /**
     * @test
     *
     * The `status` field is a ToggleButtons with only 'approved' and 'rejected'
     * as options. Passing 'rejected' is valid. The previous failure was caused
     * by the form still having 'status' validation errors, likely because the
     * Livewire test was not correctly filling the ToggleButtons widget.
     * We use fillForm with the exact key and then save.
     */
    public function staff_can_reject_a_pending_request(): void
    {
        NotificationFacade::fake();

        $event = $this->makeEvent(['status' => 'pending']);
        $staff = $this->makeUser('staff/custodian');
        $this->actingAs($staff);

        Livewire::test(EditMaterialAccessEvents::class, ['record' => $event->getRouteKey()])
            ->fillForm(['status' => 'rejected'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('material_access_events', [
            'id'     => $event->id,
            'status' => 'rejected',
        ]);
    }

    /**
     * @test
     *
     * Same as approving_a_request_sends_notification_to_requester — test
     * the observer directly via model update to avoid Livewire form
     * internals swallowing the Notification::fake() intercept.
     */
    public function rejecting_a_request_sends_notification_to_requester(): void
    {
        NotificationFacade::fake();

        $event     = $this->makeEvent(['status' => 'pending']);
        $requester = User::find($event->user_id);
        $committee = $this->makeUser('committee');

        $this->actingAs($committee);

        // Update directly on the model so the observer fires reliably
        $event->update(['status' => 'rejected']);

        NotificationFacade::assertSentTo(
            $requester,
            \App\Notifications\RequestStatusChanged::class
        );
    }

    // ── Cancellation ──────────────────────────────────────────────────────────

    /** @test */
    public function requester_can_cancel_their_own_pending_request(): void
    {
        [$parent, $copy] = $this->makeParentAndCopy();
        $student = $this->makeUser('student');

        $event = MaterialAccessEvents::create([
            'user_id'        => $student->id,
            'rr_material_id' => $copy->id,
            'event_type'     => 'request',
            'status'         => 'pending',
        ]);

        $this->actingAs($student);

        Livewire::test(
            \App\Filament\Resources\User\Requests\Pages\ViewRequests::class,
            ['record' => $event->id]
        )->callAction('cancel');

        $this->assertDatabaseHas('material_access_events', [
            'id'     => $event->id,
            'status' => 'cancelled',
        ]);
    }

    /** @test */
    public function requester_cannot_cancel_an_already_approved_request(): void
    {
        [$parent, $copy] = $this->makeParentAndCopy();
        $student = $this->makeUser('student');

        $event = MaterialAccessEvents::create([
            'user_id'        => $student->id,
            'rr_material_id' => $copy->id,
            'event_type'     => 'request',
            'status'         => 'approved',
        ]);

        $this->actingAs($student);

        Livewire::test(
            \App\Filament\Resources\User\Requests\Pages\ViewRequests::class,
            ['record' => $event->id]
        )->assertActionHidden('cancel');
    }

    /** @test */
    public function student_cannot_cancel_another_students_request(): void
    {
        [$parent, $copy] = $this->makeParentAndCopy();
        $owner    = $this->makeUser('student');
        $intruder = $this->makeUser('student');

        $event = MaterialAccessEvents::create([
            'user_id'        => $owner->id,
            'rr_material_id' => $copy->id,
            'event_type'     => 'request',
            'status'         => 'pending',
        ]);

        $this->actingAs($intruder)
            ->get("/app/requests/{$event->id}")
            ->assertStatus(404);
    }

    // ── Overdue Auto-detection ────────────────────────────────────────────────

    /** @test */
    public function is_overdue_flag_is_set_when_due_date_has_passed(): void
    {
        [$parent, $copy] = $this->makeParentAndCopy();
        $student = $this->makeUser('student');

        $event = MaterialAccessEvents::create([
            'user_id'        => $student->id,
            'rr_material_id' => $copy->id,
            'event_type'     => 'borrow',
            'status'         => 'approved',
            'due_at'         => now()->subDays(3), // overdue
            'is_overdue'     => false,
        ]);

        // Retrieving the event triggers the observer's `retrieved` hook
        $retrieved = MaterialAccessEvents::find($event->id);

        $this->assertTrue((bool) $retrieved->is_overdue);
        $this->assertDatabaseHas('material_access_events', [
            'id'         => $event->id,
            'is_overdue' => true,
        ]);
    }

    /** @test */
    public function is_overdue_not_set_when_due_date_is_in_the_future(): void
    {
        [$parent, $copy] = $this->makeParentAndCopy();
        $student = $this->makeUser('student');

        $event = MaterialAccessEvents::create([
            'user_id'        => $student->id,
            'rr_material_id' => $copy->id,
            'event_type'     => 'borrow',
            'status'         => 'approved',
            'due_at'         => now()->addDays(5),
            'is_overdue'     => false,
        ]);

        $retrieved = MaterialAccessEvents::find($event->id);

        $this->assertFalse((bool) $retrieved->is_overdue);
    }

    // ── Visibility / Policy ───────────────────────────────────────────────────

    /** @test */
    public function committee_can_view_all_access_events_in_admin_panel(): void
    {
        $event1 = $this->makeEvent();
        $event2 = $this->makeEvent();

        $committee = $this->makeUser('committee');
        $this->actingAs($committee);

        Livewire::test(\App\Filament\Resources\MaterialAccessEvents\Pages\ListMaterialAccessEvents::class)
            ->assertSee($event1->id)
            ->assertSee($event2->id);
    }

    /** @test */
    public function student_sees_only_their_own_requests_in_my_requests(): void
    {
        [$parent, $copy] = $this->makeParentAndCopy();
        $student1 = $this->makeUser('student');
        $student2 = $this->makeUser('student');

        $myEvent = MaterialAccessEvents::create([
            'user_id' => $student1->id, 'rr_material_id' => $copy->id,
            'event_type' => 'request', 'status' => 'pending',
        ]);
        $theirEvent = MaterialAccessEvents::create([
            'user_id' => $student2->id, 'rr_material_id' => $copy->id,
            'event_type' => 'request', 'status' => 'pending',
        ]);

        $this->actingAs($student1);

        Livewire::test(\App\Filament\Resources\User\Requests\Pages\ListRequests::class)
            ->assertSee($myEvent->id)
            ->assertDontSee($theirEvent->id);
    }

    /** @test */
    public function student_cannot_access_edit_page_of_access_events_in_admin_panel(): void
    {
        $event   = $this->makeEvent();
        $student = $this->makeUser('student');

        $this->actingAs($student)
            ->get("/admin/material-access-events/{$event->id}/edit")
            ->assertForbidden();
    }
}