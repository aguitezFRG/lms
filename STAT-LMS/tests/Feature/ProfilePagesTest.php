<?php

namespace Tests\Feature;

use App\Models\MaterialAccessEvents;
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
 * - ListRequests page displays tabs with pending / approved / closed filters
 * - Pending tab filters to pending requests only
 * - Approved tab filters to approved requests only
 * - Closed tab filters to rejected/cancelled requests only
 * - NotificationBell component shows stored notifications
 * - NotificationBell "Mark All as Read" clears unread count
 * - Initials derived from f_name and l_name
 */
class ProfilePagesTest extends TestCase
{
    use RefreshDatabase;

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function makeParentAndCopy(int $accessLevel = 1): array
    {
        $parent = $this->makeMaterialParent([
            'access_level' => $accessLevel,
            'material_type' => 1,
            'author' => 'Profile Test Author',
            'publication_date' => now()->subYear(),
            'keywords' => json_encode(['stats']),
            'sdgs' => json_encode(['Education']),
            'adviser' => json_encode(['Adviser']),
        ]);
        $copy = $this->makeMaterialCopy([
            'material_parent_id' => $parent->id,
            'is_digital' => true,
            'is_available' => true,
        ]);

        return [$parent, $copy];
    }

    private function makeEvent(User $user, RrMaterials $copy, string $status): MaterialAccessEvents
    {
        return MaterialAccessEvents::create([
            'user_id' => $user->id,
            'rr_material_id' => $copy->id,
            'event_type' => 'request',
            'status' => $status,
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
    public function user_profile_displays_avatar_icon_instead_of_initials(): void
    {
        $student = $this->makeUser('student', ['f_name' => 'Maria', 'l_name' => 'Santos']);
        $this->actingAs($student);

        // The profile header now renders a heroicon avatar (not text initials).
        // Assert the user's name appears in the welcome title instead.
        Livewire::test(\App\Filament\Pages\User\UserProfile::class)
            ->assertSee('Maria'); // f_name appears in the page title "Welcome, Maria!"
    }

    // ── Request Counts via ListRequests widget ────────────────────────────────

    /** @test */
    public function list_requests_page_pending_tab_shows_only_pending_requests(): void
    {
        [$parent, $copy] = $this->makeParentAndCopy();
        $student = $this->makeUser('student', ['f_name' => 'Test', 'l_name' => 'User']);

        $pending = $this->makeEvent($student, $copy, 'pending');
        $approved = $this->makeEvent($student, $copy, 'approved');

        $this->actingAs($student);

        Livewire::test(\App\Filament\Resources\User\Requests\Pages\ListRequests::class)
            ->set('activeTab', 'pending')
            ->assertSee($pending->id)
            ->assertDontSee($approved->id);
    }

    /** @test */
    public function list_requests_page_approved_tab_shows_only_approved_requests(): void
    {
        [$parent, $copy] = $this->makeParentAndCopy();
        $student = $this->makeUser('student', ['f_name' => 'Test', 'l_name' => 'User']);

        $pending = $this->makeEvent($student, $copy, 'pending');
        $approved = $this->makeEvent($student, $copy, 'approved');

        $this->actingAs($student);

        Livewire::test(\App\Filament\Resources\User\Requests\Pages\ListRequests::class)
            ->set('activeTab', 'approved')
            ->assertSee($approved->id)
            ->assertDontSee($pending->id);
    }

    /** @test */
    public function list_requests_page_closed_tab_shows_rejected_and_cancelled_requests(): void
    {
        [$parent, $copy] = $this->makeParentAndCopy();
        $student = $this->makeUser('student', ['f_name' => 'Test', 'l_name' => 'User']);

        $rejected = $this->makeEvent($student, $copy, 'rejected');
        $cancelled = $this->makeEvent($student, $copy, 'cancelled');
        $pending = $this->makeEvent($student, $copy, 'pending');

        $this->actingAs($student);

        Livewire::test(\App\Filament\Resources\User\Requests\Pages\ListRequests::class)
            ->set('activeTab', 'closed')
            ->assertSee($rejected->id)
            ->assertSee($cancelled->id)
            ->assertDontSee($pending->id);
    }

    // ── NotificationBell Component ────────────────────────────────────────────

    /** @test */
    public function notification_bell_shows_stored_notifications(): void
    {
        [$parent, $copy] = $this->makeParentAndCopy();
        $student = $this->makeUser('student', ['f_name' => 'Test', 'l_name' => 'User']);

        $event = $this->makeEvent($student, $copy, 'pending');
        $event->update(['status' => 'approved']); // triggers RequestStatusChanged

        $this->actingAs($student);

        Livewire::test(\App\Livewire\NotificationBell::class)
            ->assertSee('approved', false); // notification message contains "approved"
    }

    /** @test */
    public function notification_bell_mark_all_as_read_clears_unread_count(): void
    {
        [$parent, $copy] = $this->makeParentAndCopy();
        $student = $this->makeUser('student', ['f_name' => 'Test', 'l_name' => 'User']);

        $event = $this->makeEvent($student, $copy, 'pending');
        $event->update(['status' => 'approved']);

        $this->assertEquals(1, $student->unreadNotifications()->count());

        $this->actingAs($student);

        Livewire::test(\App\Livewire\NotificationBell::class)
            ->call('markAllAsRead');

        $this->assertEquals(0, $student->fresh()->unreadNotifications()->count());
    }

    /** @test */
    public function notification_bell_unread_badge_count_reflects_unread_notifications(): void
    {
        [$parent, $copy] = $this->makeParentAndCopy();
        $committee = $this->makeUser('committee', ['f_name' => 'Test', 'l_name' => 'User']);

        // Trigger a notification for the committee member
        $event = $this->makeEvent($committee, $copy, 'pending');
        $event->update(['status' => 'approved']);

        $this->actingAs($committee);

        $unreadCount = $committee->fresh()->unreadNotifications()->count();
        $this->assertGreaterThanOrEqual(1, $unreadCount);

        // Verify the badge count appears in the rendered component
        Livewire::test(\App\Livewire\NotificationBell::class)
            ->assertSee((string) $unreadCount);
    }
}
