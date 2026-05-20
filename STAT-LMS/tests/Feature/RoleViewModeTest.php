<?php

namespace Tests\Feature;

use App\Filament\Resources\MaterialAccessEvents\MaterialAccessEventsResource;
use App\Filament\Resources\RepositoryChangeLogs\RepositoryChangeLogsResource;
use App\Filament\Resources\RrMaterialParents\RrMaterialParentsResource;
use App\Filament\Resources\RrMaterials\RrMaterialsResource;
use App\Filament\Resources\User\Catalogs\CatalogResource;
use App\Filament\Resources\User\Catalogs\Pages\ListCatalogs;
use App\Filament\Resources\User\Requests\RequestsResource;
use App\Filament\Resources\Users\UserResource;
use App\Models\MaterialAccessEvents;
use App\Models\RrMaterialParents;
use App\Models\RrMaterials;
use App\Support\RoleViewMode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleViewModeTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_set_and_clear_role_view_mode(): void
    {
        $admin = $this->makeUser('super_admin');

        $this->actingAs($admin)
            ->post(route('role-view-mode.update'), ['role' => 'student'])
            ->assertRedirect('/admin/user-onboarding');

        $this->assertSame('student', session(RoleViewMode::SESSION_KEY));

        $this->actingAs($admin)
            ->withSession([RoleViewMode::SESSION_KEY => 'student'])
            ->post(route('role-view-mode.update'), ['role' => 'actual'])
            ->assertRedirect('/admin/admin-onboarding');

        $this->assertNull(session(RoleViewMode::SESSION_KEY));
    }

    public function test_committee_can_set_role_view_mode(): void
    {
        $committee = $this->makeUser('committee');

        $this->actingAs($committee)
            ->post(route('role-view-mode.update'), ['role' => 'faculty'])
            ->assertRedirect();

        $this->assertSame('faculty', session(RoleViewMode::SESSION_KEY));
    }

    public function test_invalid_role_view_mode_is_rejected(): void
    {
        $admin = $this->makeUser('super_admin');

        $this->actingAs($admin)
            ->from('/admin')
            ->post(route('role-view-mode.update'), ['role' => 'it'])
            ->assertRedirect('/admin')
            ->assertSessionHasErrors('role');

        $this->assertNull(session(RoleViewMode::SESSION_KEY));
    }

    public function test_non_operators_cannot_use_role_view_mode_endpoint(): void
    {
        foreach (['it', 'staff/custodian', 'faculty', 'student'] as $role) {
            $user = $this->makeUser($role);

            $this->actingAs($user)
                ->post(route('role-view-mode.update'), ['role' => 'student'])
                ->assertForbidden();
        }
    }

    public function test_student_preview_filters_material_parent_and_copy_queries_to_student_level(): void
    {
        $admin = $this->makeUser('super_admin');
        $public = $this->makeParentWithCopy(1, 'Public Material');
        $restricted = $this->makeParentWithCopy(2, 'Restricted Material');
        $confidential = $this->makeParentWithCopy(3, 'Confidential Material');

        $this->actingAs($admin)->withSession([RoleViewMode::SESSION_KEY => 'student']);

        $this->assertEqualsCanonicalizing(
            [$public->id],
            RrMaterialParentsResource::getEloquentQuery()->pluck('id')->all(),
        );

        $this->assertEqualsCanonicalizing(
            [$public->materials()->firstOrFail()->id],
            RrMaterialsResource::getEloquentQuery()->pluck('id')->all(),
        );

        $this->assertFalse($admin->can('update', $public));
        $this->assertFalse($admin->can('view', $restricted));
        $this->assertFalse($admin->can('view', $confidential));
    }

    public function test_faculty_preview_filters_material_parent_and_copy_queries_to_faculty_level(): void
    {
        $admin = $this->makeUser('committee');
        $public = $this->makeParentWithCopy(1, 'Public Material');
        $restricted = $this->makeParentWithCopy(2, 'Restricted Material');
        $confidential = $this->makeParentWithCopy(3, 'Confidential Material');

        $this->actingAs($admin)->withSession([RoleViewMode::SESSION_KEY => 'faculty']);

        $this->assertEqualsCanonicalizing(
            [$public->id, $restricted->id],
            RrMaterialParentsResource::getEloquentQuery()->pluck('id')->all(),
        );

        $this->assertEqualsCanonicalizing(
            [
                $public->materials()->firstOrFail()->id,
                $restricted->materials()->firstOrFail()->id,
            ],
            RrMaterialsResource::getEloquentQuery()->pluck('id')->all(),
        );

        $this->assertFalse($admin->can('update', $restricted));
        $this->assertFalse($admin->can('view', $confidential));
    }

    public function test_rr_staff_preview_filters_access_logs_to_physical_student_level_events(): void
    {
        $admin = $this->makeUser('super_admin');
        $student = $this->makeUser('student');
        $physicalPublic = $this->makeCopy(1, false);
        $digitalPublic = $this->makeCopy(1, true);
        $physicalFaculty = $this->makeCopy(2, false);

        $visible = MaterialAccessEvents::factory()->create([
            'user_id' => $student->id,
            'rr_material_id' => $physicalPublic->id,
            'event_type' => 'borrow',
            'status' => 'pending',
        ]);

        MaterialAccessEvents::factory()->create([
            'user_id' => $student->id,
            'rr_material_id' => $digitalPublic->id,
            'event_type' => 'request',
            'status' => 'pending',
        ]);

        MaterialAccessEvents::factory()->create([
            'user_id' => $student->id,
            'rr_material_id' => $physicalFaculty->id,
            'event_type' => 'borrow',
            'status' => 'pending',
        ]);

        $this->actingAs($admin)->withSession([RoleViewMode::SESSION_KEY => 'staff/custodian']);

        $this->assertEqualsCanonicalizing(
            [$visible->id],
            MaterialAccessEventsResource::getEloquentQuery()->pluck('id')->all(),
        );

        $this->assertFalse($admin->can('update', $visible));
    }

    public function test_lower_role_previews_hide_privileged_admin_navigation(): void
    {
        $admin = $this->makeUser('super_admin');

        $this->actingAs($admin)->withSession([RoleViewMode::SESSION_KEY => 'student']);

        $this->assertFalse(UserResource::shouldRegisterNavigation());
        $this->assertFalse(RepositoryChangeLogsResource::shouldRegisterNavigation());
        $this->assertFalse(MaterialAccessEventsResource::shouldRegisterNavigation());

        session([RoleViewMode::SESSION_KEY => 'staff/custodian']);

        $this->assertFalse(UserResource::shouldRegisterNavigation());
        $this->assertFalse(RepositoryChangeLogsResource::shouldRegisterNavigation());
        $this->assertTrue(MaterialAccessEventsResource::shouldRegisterNavigation());
    }

    public function test_student_and_faculty_previews_expose_user_role_pages_in_admin_panel(): void
    {
        $admin = $this->makeUser('super_admin');

        foreach (['student' => 'Student User', 'faculty' => 'Faculty Member'] as $role => $roleLabel) {
            $this->actingAs($admin)->withSession([RoleViewMode::SESSION_KEY => $role]);

            $this->get('/admin/user-onboarding')
                ->assertOk()
                ->assertSeeText($roleLabel)
                ->assertSeeText('Catalog')
                ->assertSeeText('My Requests')
                ->assertDontSeeText('RR Materials')
                ->assertDontSeeText('Material Copy')
                ->assertDontSeeText('System Usage');

            $this->get('/admin/user/catalogs')->assertOk();
            $this->get('/admin/user/requests')->assertOk();

            $this->assertTrue(CatalogResource::shouldRegisterNavigation());
            $this->assertTrue(RequestsResource::shouldRegisterNavigation());
            $this->assertFalse(RrMaterialParentsResource::shouldRegisterNavigation());
            $this->assertFalse(RrMaterialsResource::shouldRegisterNavigation());
            $this->assertFalse(MaterialAccessEventsResource::canViewAny());
        }
    }

    public function test_rr_staff_preview_keeps_staff_admin_navigation_pages_available(): void
    {
        $admin = $this->makeUser('super_admin');

        $this->actingAs($admin)->withSession([RoleViewMode::SESSION_KEY => 'staff/custodian']);

        $this->get('/admin/admin-onboarding')
            ->assertOk()
            ->assertSeeText('Reading Room Staff')
            ->assertSeeText('RR Materials')
            ->assertSeeText('Material Copy')
            ->assertSeeText('Material Access Logs')
            ->assertSeeText('System Usage')
            ->assertDontSeeText('Users')
            ->assertDontSeeText('Repository Change Logs');

        $this->get('/admin')->assertOk();
        $this->get('/admin/system-usage')->assertOk();

        $this->assertTrue(RrMaterialParentsResource::shouldRegisterNavigation());
        $this->assertTrue(RrMaterialsResource::shouldRegisterNavigation());
        $this->assertTrue(MaterialAccessEventsResource::shouldRegisterNavigation());
        $this->assertFalse(UserResource::shouldRegisterNavigation());
        $this->assertFalse(RepositoryChangeLogsResource::shouldRegisterNavigation());
    }

    public function test_student_preview_filters_catalog_listing_to_student_level(): void
    {
        $admin = $this->makeUser('super_admin');
        $public = $this->makeParentWithCopy(1, 'Public Material');
        $this->makeParentWithCopy(2, 'Faculty Material');
        $this->makeParentWithCopy(3, 'Committee Material');

        $this->actingAs($admin)->withSession([RoleViewMode::SESSION_KEY => 'student']);

        $page = new class extends ListCatalogs {
            public function publicQuery(): \Illuminate\Database\Eloquent\Builder
            {
                return $this->getQuery();
            }
        };

        $this->assertEqualsCanonicalizing(
            [$public->id],
            $page->publicQuery()->pluck('id')->all(),
        );
    }

    public function test_faculty_preview_filters_catalog_listing_to_faculty_level(): void
    {
        $admin = $this->makeUser('committee');
        $public = $this->makeParentWithCopy(1, 'Public Material');
        $faculty = $this->makeParentWithCopy(2, 'Faculty Material');
        $this->makeParentWithCopy(3, 'Committee Material');

        $this->actingAs($admin)->withSession([RoleViewMode::SESSION_KEY => 'faculty']);

        $page = new class extends ListCatalogs {
            public function publicQuery(): \Illuminate\Database\Eloquent\Builder
            {
                return $this->getQuery();
            }
        };

        $this->assertEqualsCanonicalizing(
            [$public->id, $faculty->id],
            $page->publicQuery()->pluck('id')->all(),
        );
    }

    public function test_student_preview_blocks_direct_url_access_to_high_access_catalog_record(): void
    {
        $admin = $this->makeUser('super_admin');
        $confidential = $this->makeParentWithCopy(3, 'Confidential Material');

        $this->actingAs($admin)
            ->withSession([RoleViewMode::SESSION_KEY => 'student'])
            ->get('/admin/user/catalogs/'.$confidential->id)
            ->assertStatus(404);
    }

    public function test_role_switch_redirects_to_user_onboarding_for_student_preview(): void
    {
        $admin = $this->makeUser('super_admin');

        $this->actingAs($admin)
            ->post(route('role-view-mode.update'), ['role' => 'student'])
            ->assertRedirect('/admin/user-onboarding');
    }

    public function test_role_switch_redirects_to_user_onboarding_for_faculty_preview(): void
    {
        $admin = $this->makeUser('super_admin');

        $this->actingAs($admin)
            ->post(route('role-view-mode.update'), ['role' => 'faculty'])
            ->assertRedirect('/admin/user-onboarding');
    }

    public function test_role_switch_redirects_to_admin_onboarding_for_rr_preview(): void
    {
        $admin = $this->makeUser('super_admin');

        $this->actingAs($admin)
            ->post(route('role-view-mode.update'), ['role' => 'staff/custodian'])
            ->assertRedirect('/admin/admin-onboarding');
    }

    public function test_role_clear_redirects_to_admin_onboarding(): void
    {
        $admin = $this->makeUser('super_admin');

        $this->actingAs($admin)
            ->withSession([RoleViewMode::SESSION_KEY => 'student'])
            ->post(route('role-view-mode.update'), ['role' => 'actual'])
            ->assertRedirect('/admin/admin-onboarding');
    }

    private function makeParentWithCopy(int $accessLevel, string $title): RrMaterialParents
    {
        $parent = $this->makeMaterialParent([
            'access_level' => $accessLevel,
            'title' => $title,
        ]);

        $this->makeMaterialCopy([
            'material_parent_id' => $parent->id,
            'is_digital' => true,
            'is_available' => true,
        ]);

        return $parent->refresh();
    }

    private function makeCopy(int $accessLevel, bool $isDigital): RrMaterials
    {
        $parent = $this->makeMaterialParent([
            'access_level' => $accessLevel,
        ]);

        return $this->makeMaterialCopy([
            'material_parent_id' => $parent->id,
            'is_digital' => $isDigital,
            'is_available' => true,
            'file_name' => $isDigital ? 'repository/access_level_'.$accessLevel.'/test.pdf' : null,
        ]);
    }
}
