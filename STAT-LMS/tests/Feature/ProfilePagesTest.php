<?php

namespace Tests\Feature;

use App\Models\MaterialAccessEvents;
use App\Models\RrMaterialParents;
use App\Models\RrMaterials;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Feature: Profile Pages (AdminProfile & UserProfile)
 *
 * Covers:
 * - AdminProfile accessible only to admin-panel roles (committee, IT, staff)
 * - UserProfile accessible only to user-panel roles (faculty, student)
 * - UserProfile displays correct pending / approved / total counts
 * - Tab switching updates the displayed request table
 * - Pending tab shows only pending requests
 * - Approved tab shows only approved requests
 * - Closed tab shows rejected and cancelled requests
 * - Notifications tab shows database notifications
 * - "Mark All as Read" action clears unread count
 * - Initials derived from f_name and l_name
 * - AdminProfile history table scoped to authenticated admin user
 * - Notifications tab badge count reflects unread count
 */
class ProfilePagesTest extends TestCase
{
    use RefreshDatabase;

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function makeParentAndCopy(int $accessLevel = 1): array
    {
        $parent = $this->makeMaterialParent([
            'access_level'     => $accessLevel,
            'material_type'    => 1,
            'author'           => 'Profile Test Author',
            'publication_date' => now()->subYear(),
            'keywords'         => json_encode(['stats']),
            'sdgs'             => json_encode(['Education']),
            'adviser'          => json_encode(['Adviser']),
        ]);
        $copy = $this->makeMaterialCopy([
            'material_parent_id' => $parent->id,
            'is_digital'         => true,
            'is_available'       => true,
        ]);
        return [$parent, $copy];
    }

    private function makeEvent(User $user, RrMaterials $copy, string $status): MaterialAccessEvents
    {
        return MaterialAccessEvents::create([
            'user_id'        => $user->id,
            'rr_material_id' => $copy->id,
            'event_type'     => 'request',
            'status'         => $status,
        ]);
    }

    // ── Route Access ──────────────────────────────────────────────────────────

    /** @test */
    public function committee_member_can_access_admin_profile_page(): void
    {
        $committee = $this->makeUser('committee', ['f_name' => 'Test', 'l_name' => 'User']);
        $this->actingAs($committee);

        Livewire::test(\App\Filament\Pages\Auth\AdminProfile::class)
            ->assertSuccessful();
    }

    /** @test */
    public function student_cannot_access_admin_profile_page(): void
    {
        $student = $this->makeUser('student', ['f_name' => 'Test', 'l_name' => 'User']);

        $this->actingAs($student)
            ->get('/admin/profile')
            ->assertStatus(404);
    }

    /** @test */
    public function student_can_access_user_profile_page(): void
    {
        $student = $this->makeUser('student', ['f_name' => 'Test', 'l_name' => 'User']);
        $this->actingAs($student);

        Livewire::test(\App\Filament\Pages\User\UserProfile::class)
            ->assertSuccessful();
    }

    /** @test */
    public function committee_cannot_access_user_profile_page(): void
    {
        $committee = $this->makeUser('committee', ['f_name' => 'Test', 'l_name' => 'User']);

        $this->actingAs($committee)
            ->get('/app/profile')
            ->assertStatus(404);
    }

    // ── Initials ──────────────────────────────────────────────────────────────

    /** @test */
    public function user_profile_displays_correct_initials_from_name_parts(): void
    {
        $student = $this->makeUser('student', ['f_name' => 'Maria', 'l_name' => 'Santos']);
        $this->actingAs($student);

        Livewire::test(\App\Filament\Pages\User\UserProfile::class)
            ->assertSee('MS'); // initials
    }

    // ── Request Counts ────────────────────────────────────────────────────────

    /** @test */
    public function user_profile_displays_correct_pending_count(): void
    {
        [$parent, $copy] = $this->makeParentAndCopy();
        $student = $this->makeUser('student', ['f_name' => 'Test', 'l_name' => 'User']);

        $this->makeEvent($student, $copy, 'pending');
        $this->makeEvent($student, $copy, 'pending');
        $this->makeEvent($student, $copy, 'approved');

        $this->actingAs($student);

        Livewire::test(\App\Filament\Pages\User\UserProfile::class)
            ->assertSeeHtml((string) MaterialAccessEvents::where('user_id', $student->id)->where('status', 'pending')->count()); // pending count badge
    }

    /** @test */
    public function user_profile_displays_correct_approved_count(): void
    {
        [$parent, $copy] = $this->makeParentAndCopy();
        $student = $this->makeUser('student', ['f_name' => 'Test', 'l_name' => 'User']);

        $this->makeEvent($student, $copy, 'approved');
        $this->makeEvent($student, $copy, 'pending');

        $this->actingAs($student);

        Livewire::test(\App\Filament\Pages\User\UserProfile::class)
            ->assertSee((string) MaterialAccessEvents::where('user_id', $student->id)->where('status', 'approved')->count()); // approved count badge
    }

    // ── Tab Switching ─────────────────────────────────────────────────────────

    /** @test */
    public function pending_tab_shows_only_pending_requests(): void
    {
        [$parent, $copy] = $this->makeParentAndCopy();
        $student = $this->makeUser('student', ['f_name' => 'Test', 'l_name' => 'User']);

        $pending  = $this->makeEvent($student, $copy, 'pending');
        $approved = $this->makeEvent($student, $copy, 'approved');

        $this->actingAs($student);

        Livewire::test(\App\Filament\Pages\User\UserProfile::class)
            ->call('setTab', 'pending')
            ->assertSee($pending->id)
            ->assertDontSee($approved->id);
    }

    /** @test */
    public function approved_tab_shows_only_approved_requests(): void
    {
        [$parent, $copy] = $this->makeParentAndCopy();
        $student = $this->makeUser('student', ['f_name' => 'Test', 'l_name' => 'User']);

        $pending  = $this->makeEvent($student, $copy, 'pending');
        $approved = $this->makeEvent($student, $copy, 'approved');

        $this->actingAs($student);

        Livewire::test(\App\Filament\Pages\User\UserProfile::class)
            ->call('setTab', 'approved')
            ->assertSee($approved->id)
            ->assertDontSee($pending->id);
    }

    /** @test */
    public function closed_tab_shows_rejected_and_cancelled_requests(): void
    {
        [$parent, $copy] = $this->makeParentAndCopy();
        $student = $this->makeUser('student', ['f_name' => 'Test', 'l_name' => 'User']);

        $rejected  = $this->makeEvent($student, $copy, 'rejected');
        $cancelled = $this->makeEvent($student, $copy, 'cancelled');
        $pending   = $this->makeEvent($student, $copy, 'pending');

        $this->actingAs($student);

        Livewire::test(\App\Filament\Pages\User\UserProfile::class)
            ->call('setTab', 'closed')
            ->assertSee($rejected->id)
            ->assertSee($cancelled->id)
            ->assertDontSee($pending->id);
    }

    // ── Notifications Tab ─────────────────────────────────────────────────────

    /** @test */
    public function notifications_tab_shows_stored_notifications(): void
    {
        [$parent, $copy] = $this->makeParentAndCopy();
        $student = $this->makeUser('student', ['f_name' => 'Test', 'l_name' => 'User']);

        $event = $this->makeEvent($student, $copy, 'pending');
        $event->update(['status' => 'approved']); // triggers RequestStatusChanged

        $this->actingAs($student);

        Livewire::test(\App\Filament\Pages\User\UserProfile::class)
            ->call('setTab', 'notifications')
            ->assertSee('approved', false); // notification message contains "Approved"
    }

    /** @test */
    public function mark_all_as_read_action_clears_unread_count(): void
    {
        [$parent, $copy] = $this->makeParentAndCopy();
        $student = $this->makeUser('student', ['f_name' => 'Test', 'l_name' => 'User']);

        $event = $this->makeEvent($student, $copy, 'pending');
        $event->update(['status' => 'approved']);

        $this->assertEquals(1, $student->unreadNotifications()->count());

        $this->actingAs($student);

        Livewire::test(\App\Filament\Pages\User\UserProfile::class)
            ->call('setTab', 'notifications')
            ->callAction('markAllRead');

        $this->assertEquals(0, $student->fresh()->unreadNotifications()->count());
    }

    // ── AdminProfile History Table ────────────────────────────────────────────

    /** @test */
    public function admin_profile_history_scoped_to_current_admin_user(): void
    {
        [$parent, $copy] = $this->makeParentAndCopy();
        $committee  = $this->makeUser('committee', ['f_name' => 'Test', 'l_name' => 'User']);
        $otherAdmin = $this->makeUser('it', ['f_name' => 'Test', 'l_name' => 'User']);

        // Create events for both admins
        $myEvent    = $this->makeEvent($committee, $copy, 'approved');
        $otherEvent = $this->makeEvent($otherAdmin, $copy, 'pending');

        $this->actingAs($committee);

        Livewire::test(\App\Filament\Pages\Auth\AdminProfile::class)
            ->assertSee($myEvent->id)
            ->assertDontSee($otherEvent->id);
    }

    /** @test */
    public function admin_profile_notifications_tab_shows_unread_badge(): void
    {
        [$parent, $copy] = $this->makeParentAndCopy();
        $committee = $this->makeUser('committee', ['f_name' => 'Test', 'l_name' => 'User']);

        // Trigger a notification for the committee member
        $event = $this->makeEvent($committee, $copy, 'pending');
        $event->update(['status' => 'approved']);

        $this->actingAs($committee);

        Livewire::test(\App\Filament\Pages\Auth\AdminProfile::class)
            ->assertSee(fn ($data) => $data['unreadCount'] >= 1);
    }
}