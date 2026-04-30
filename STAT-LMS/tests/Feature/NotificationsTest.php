<?php

namespace Tests\Feature;

use App\Models\MaterialAccessEvents;
use App\Models\RrMaterialParents;
use App\Models\User;
use App\Notifications\AccessLevelChanged;
use App\Notifications\AccountDetailsChanged;
use App\Notifications\BorrowDueSoon;
use App\Notifications\RequestStatusChanged;
use Illuminate\Auth\Events\Login;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Feature: Notification System
 */
class NotificationsTest extends TestCase
{
    use RefreshDatabase;

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function makeParentAndCopy(int $accessLevel = 1, bool $digital = true): array
    {
        $parent = $this->makeMaterialParent([
            'access_level' => $accessLevel,
            'material_type' => 1,
            'author' => 'Notification Test Author',
            'publication_date' => now()->subYear(),
            'keywords' => json_encode(['stats']),
            'sdgs' => json_encode(['Education']),
            'adviser' => json_encode(['Adviser']),
        ]);

        $copy = $this->makeMaterialCopy([
            'material_parent_id' => $parent->id,
            'is_digital' => $digital,
            'is_available' => true,
            'file_name' => $digital ? 'repo/file.pdf' : null,
        ]);

        return [$parent, $copy];
    }

    // ── RequestStatusChanged ──────────────────────────────────────────────────

    #[Test]
    public function notification_is_sent_when_request_is_approved(): void
    {
        Notification::fake();

        [$parent, $copy] = $this->makeParentAndCopy();
        $student = $this->makeUser('student');

        $event = MaterialAccessEvents::create([
            'user_id' => $student->id,
            'rr_material_id' => $copy->id,
            'event_type' => 'request',
            'status' => 'pending',
        ]);

        $event->update(['status' => 'approved']);

        Notification::assertSentTo($student, RequestStatusChanged::class);
    }

    #[Test]
    public function notification_is_sent_when_request_is_rejected(): void
    {
        Notification::fake();

        [$parent, $copy] = $this->makeParentAndCopy();
        $student = $this->makeUser('student');

        $event = MaterialAccessEvents::create([
            'user_id' => $student->id,
            'rr_material_id' => $copy->id,
            'event_type' => 'request',
            'status' => 'pending',
        ]);

        $event->update(['status' => 'rejected']);

        Notification::assertSentTo($student, RequestStatusChanged::class);
    }

    #[Test]
    public function notification_is_not_sent_when_request_is_cancelled(): void
    {
        Notification::fake();

        [$parent, $copy] = $this->makeParentAndCopy();
        $student = $this->makeUser('student');

        $event = MaterialAccessEvents::create([
            'user_id' => $student->id,
            'rr_material_id' => $copy->id,
            'event_type' => 'request',
            'status' => 'pending',
        ]);

        $event->update(['status' => 'cancelled']);

        Notification::assertNotSentTo($student, RequestStatusChanged::class);
    }

    #[Test]
    public function request_status_changed_notification_is_stored_in_database(): void
    {
        [$parent, $copy] = $this->makeParentAndCopy();
        $student = $this->makeUser('student');

        $event = MaterialAccessEvents::create([
            'user_id' => $student->id,
            'rr_material_id' => $copy->id,
            'event_type' => 'request',
            'status' => 'pending',
        ]);

        $event->update(['status' => 'approved']);

        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => $student->id,
        ]);
    }

    // ── AccountDetailsChanged ─────────────────────────────────────────────────

    #[Test]
    public function notification_sent_when_admin_changes_another_users_fields(): void
    {
        Notification::fake();

        $committee = $this->makeUser('committee');
        $target = $this->makeUser('student');

        $this->actingAs($committee);
        $target->update(['f_name' => 'ChangedName']);

        Notification::assertSentTo($target, AccountDetailsChanged::class);
    }

    #[Test]
    public function notification_not_sent_when_user_updates_own_account(): void
    {
        Notification::fake();

        $user = $this->makeUser('student');
        $this->actingAs($user);

        $user->update(['f_name' => 'SelfUpdate']);

        Notification::assertNotSentTo($user, AccountDetailsChanged::class);
    }

    #[Test]
    public function account_details_changed_notification_lists_changed_fields(): void
    {
        $committee = $this->makeUser('committee');
        $target = $this->makeUser('student');

        $this->actingAs($committee);
        $target->update(['f_name' => 'NewFirst', 'l_name' => 'NewLast']);

        $notification = $target->notifications()->first();
        $this->assertNotNull($notification);
        $this->assertStringContainsString('F name', $notification->data['message']);
    }

    // ── BorrowDueSoon (Login Listener) ────────────────────────────────────────

    #[Test]
    public function borrow_due_tomorrow_notification_sent_on_login(): void
    {
        Notification::fake();

        [$parent, $copy] = $this->makeParentAndCopy(1, digital: false);
        $student = $this->makeUser('student');

        MaterialAccessEvents::create([
            'user_id' => $student->id,
            'rr_material_id' => $copy->id,
            'event_type' => 'borrow',
            'status' => 'approved',
            'due_at' => now()->addDay()->endOfDay(),
        ]);

        event(new Login('web', $student, false));

        Notification::assertSentTo(
            $student,
            fn (BorrowDueSoon $n) => $n->toDatabase($student)['days_until_due'] === 1
        );
    }

    #[Test]
    public function borrow_due_in_3_days_notification_sent_on_login(): void
    {
        Notification::fake();

        [$parent, $copy] = $this->makeParentAndCopy(1, digital: false);
        $student = $this->makeUser('student');

        MaterialAccessEvents::create([
            'user_id' => $student->id,
            'rr_material_id' => $copy->id,
            'event_type' => 'borrow',
            'status' => 'approved',
            'due_at' => now()->addDays(3)->endOfDay(),
        ]);

        event(new Login('web', $student, false));

        Notification::assertSentTo(
            $student,
            fn (BorrowDueSoon $n) => $n->toDatabase($student)['days_until_due'] === 3
        );
    }

    #[Test]
    public function already_returned_borrow_does_not_trigger_due_soon(): void
    {
        Notification::fake();

        [$parent, $copy] = $this->makeParentAndCopy(1, digital: false);
        $student = $this->makeUser('student');

        MaterialAccessEvents::create([
            'user_id' => $student->id,
            'rr_material_id' => $copy->id,
            'event_type' => 'borrow',
            'status' => 'approved',
            'due_at' => now()->addDay()->endOfDay(),
            'returned_at' => now(),
        ]);

        event(new Login('web', $student, false));

        Notification::assertNotSentTo($student, BorrowDueSoon::class);
    }

    #[Test]
    public function borrow_due_soon_not_triggered_for_digital_requests(): void
    {
        Notification::fake();

        [$parent, $copy] = $this->makeParentAndCopy(1, digital: true);
        $student = $this->makeUser('student');

        MaterialAccessEvents::create([
            'user_id' => $student->id,
            'rr_material_id' => $copy->id,
            'event_type' => 'request',
            'status' => 'approved',
            'due_at' => now()->addDay()->endOfDay(),
        ]);

        event(new Login('web', $student, false));

        Notification::assertNotSentTo($student, BorrowDueSoon::class);
    }

    #[Test]
    public function duplicate_due_soon_notification_suppressed_on_same_day_login(): void
    {
        [$parent, $copy] = $this->makeParentAndCopy(1, digital: false);
        $student = $this->makeUser('student');

        $borrow = MaterialAccessEvents::create([
            'user_id' => $student->id,
            'rr_material_id' => $copy->id,
            'event_type' => 'borrow',
            'status' => 'approved',
            'due_at' => now()->addDay()->endOfDay(),
        ]);

        event(new Login('web', $student, false));
        $countAfterFirstLogin = $student->notifications()->count();

        event(new Login('web', $student, false));
        $countAfterSecondLogin = $student->notifications()->count();

        $this->assertEquals($countAfterFirstLogin, $countAfterSecondLogin);
    }

    #[Test]
    public function borrow_due_soon_not_sent_for_committee_on_login(): void
    {
        Notification::fake();

        [$parent, $copy] = $this->makeParentAndCopy(1, digital: false);
        $committee = $this->makeUser('committee');

        MaterialAccessEvents::create([
            'user_id' => $committee->id,
            'rr_material_id' => $copy->id,
            'event_type' => 'borrow',
            'status' => 'approved',
            'due_at' => now()->addDay()->endOfDay(),
        ]);

        event(new Login('web', $committee, false));

        Notification::assertNotSentTo($committee, BorrowDueSoon::class);
    }

    // ── AccessLevelChanged ────────────────────────────────────────────────────

    /**
     *
     * FIX: Notification::fake() must be called BEFORE the model update that
     * triggers the notification. In the original test, fake() was called after
     * creating the MaterialAccessEvent but that is fine — the critical ordering
     * is that fake() is active when $parent->update(['access_level' => 3]) runs.
     *
     * The actual root cause of the failure was that RrMaterialParents::booted()
     * fires a static::updated() hook which calls User::notify(). However, the
     * booted() hook only fires once per request lifecycle. In tests the model
     * boot listeners may already be flushed by makeParentAndCopy() calling
     * RrMaterialParents::flushEventListeners() via makeMaterialParent().
     *
     * We work around this by re-registering the booted() listeners after the
     * helper creates the parent, ensuring the updated() hook is active when we
     * call $parent->update().
     */
    #[Test]
    public function notification_sent_to_affected_users_when_access_level_changes(): void
    {
        Notification::fake();

        [$parent, $copy] = $this->makeParentAndCopy(1);
        $student = $this->makeUser('student');

        MaterialAccessEvents::create([
            'user_id' => $student->id,
            'rr_material_id' => $copy->id,
            'event_type' => 'request',
            'status' => 'approved',
        ]);

        // Re-register RrMaterialParents boot listeners that were flushed by
        // the makeMaterialParent() helper.
        RrMaterialParents::clearBootedModels();
        $parent = RrMaterialParents::find($parent->id);

        $parent->update(['access_level' => 3]);

        Notification::assertSentTo($student, AccessLevelChanged::class);
    }

    #[Test]
    public function access_level_changed_notification_not_sent_for_other_field_updates(): void
    {
        Notification::fake();

        [$parent, $copy] = $this->makeParentAndCopy(1);
        $student = $this->makeUser('student');

        MaterialAccessEvents::create([
            'user_id' => $student->id,
            'rr_material_id' => $copy->id,
            'event_type' => 'request',
            'status' => 'approved',
        ]);

        RrMaterialParents::clearBootedModels();

        $parent->update(['title' => 'New Title Only']);

        Notification::assertNotSentTo($student, AccessLevelChanged::class);
    }

    // ── Mark As Read ──────────────────────────────────────────────────────────

    #[Test]
    public function mark_as_read_sets_read_at_timestamp(): void
    {
        [$parent, $copy] = $this->makeParentAndCopy();
        $student = $this->makeUser('student');

        $event = MaterialAccessEvents::create([
            'user_id' => $student->id,
            'rr_material_id' => $copy->id,
            'event_type' => 'request',
            'status' => 'pending',
        ]);

        $event->update(['status' => 'approved']);

        $notification = $student->unreadNotifications()->first();
        $this->assertNotNull($notification);

        $notification->markAsRead();

        $this->assertNotNull($notification->fresh()->read_at);
    }

    #[Test]
    public function mark_all_as_read_clears_all_unread_notifications(): void
    {
        [$parent, $copy] = $this->makeParentAndCopy();
        $student = $this->makeUser('student');

        foreach (['approved', 'rejected'] as $status) {
            $event = MaterialAccessEvents::create([
                'user_id' => $student->id,
                'rr_material_id' => $copy->id,
                'event_type' => 'request',
                'status' => 'pending',
            ]);
            $event->update(['status' => $status]);
        }

        $this->assertEquals(2, $student->unreadNotifications()->count());

        $student->unreadNotifications->markAsRead();

        $this->assertEquals(0, $student->unreadNotifications()->count());
    }

    // ── Artisan Command ───────────────────────────────────────────────────────

    #[Test]
    public function artisan_due_soon_command_sends_notifications_for_matching_borrows(): void
    {
        Notification::fake();

        [$parent, $copy] = $this->makeParentAndCopy(1, digital: false);
        $student = $this->makeUser('student');

        MaterialAccessEvents::create([
            'user_id' => $student->id,
            'rr_material_id' => $copy->id,
            'event_type' => 'borrow',
            'status' => 'approved',
            'due_at' => now()->addDay()->endOfDay(),
        ]);

        $this->artisan('notifications:due-soon')->assertExitCode(0);

        Notification::assertSentTo($student, BorrowDueSoon::class);
    }
}
