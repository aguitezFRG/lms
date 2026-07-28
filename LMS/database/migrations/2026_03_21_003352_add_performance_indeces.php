<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
         * ── rr_material_parents ──────────────────────────────────────────
         * Heavy filtering in ListCatalogs: access_level <= $userLevel
         * Sorting/filtering by material_type, publication_date
         * Soft-delete scope on deleted_at
         */
        Schema::table('rr_material_parents', function (Blueprint $table) {
            $table->index('access_level');
            $table->index('material_type');
            $table->index('publication_date');
            $table->index('deleted_at');
            // Composite: catalog query always filters access_level + sorts created_at
            $table->index(['access_level', 'deleted_at', 'created_at'], 'rr_parents_catalog_idx');
        });

        /*
         * ── rr_materials ─────────────────────────────────────────────────
         * Frequently joined via material_parent_id
         * Filtered by is_digital, is_available, deleted_at in many queries
         * e.g. whereHas('materials', fn($m) => $m->where('is_digital', true)->where('is_available', true)->whereNull('deleted_at'))
         */
        Schema::table('rr_materials', function (Blueprint $table) {
            $table->index('material_parent_id');
            $table->index('deleted_at');
            // Composite: the whereHas in ListCatalogs and ViewCatalog always uses all three
            $table->index(['material_parent_id', 'is_digital', 'is_available', 'deleted_at'], 'rr_materials_availability_idx');
        });

        /*
         * ── material_access_events ────────────────────────────────────────
         * Most queried table — scoped by user_id in every user-facing page
         * Filtered by status, event_type constantly
         * Overdue check: due_at + returned_at + completed_at + is_overdue
         * Notification dedup: user_id + created_at (date) in SendDueSoonOnLogin
         */
        Schema::table('material_access_events', function (Blueprint $table) {
            $table->index('user_id');
            $table->index('rr_material_id');
            $table->index('approver_id');
            $table->index('status');
            $table->index('event_type');
            $table->index('due_at');
            $table->index('deleted_at');
            // Composite: UserProfile table query — user_id + event_type + status
            $table->index(['user_id', 'event_type', 'status'], 'mae_user_type_status_idx');
            // Composite: due-soon command/listener — event_type + status + due_at + returned_at
            $table->index(['event_type', 'status', 'due_at'], 'mae_due_soon_idx');
            // Composite: overdue detection on retrieved — due_at + is_overdue + returned_at
            $table->index(['due_at', 'is_overdue', 'returned_at'], 'mae_overdue_idx');
        });

        /*
         * ── repository_change_logs ────────────────────────────────────────
         * Filtered by change_type, table_changed in admin panel
         * Sorted by changed_at (desc) always
         */
        Schema::table('repository_change_logs', function (Blueprint $table) {
            $table->index('editor_id');
            $table->index('rr_material_id');
            $table->index('target_user_id');
            $table->index('change_type');
            $table->index('table_changed');
            $table->index('changed_at');
            $table->index('deleted_at');
        });

        /*
         * ── notifications ─────────────────────────────────────────────────
         * Queried by notifiable_type + notifiable_id (polymorphic)
         * Filtered by read_at for unread count
         * Dedup query in SendDueSoonOnLogin also uses created_at date
         * (notifiable_id index already created via morphs() — skip those)
         */
        Schema::table('notifications', function (Blueprint $table) {
            $table->index('read_at');
            $table->index('created_at');
            // Composite: unread count — notifiable_id + read_at
            $table->index(['notifiable_id', 'read_at'], 'notifications_unread_idx');
        });
    }

    public function down(): void
    {
        Schema::table('rr_material_parents', function (Blueprint $table) {
            $table->dropIndex(['access_level']);
            $table->dropIndex(['material_type']);
            $table->dropIndex(['publication_date']);
            $table->dropIndex(['deleted_at']);
            $table->dropIndex('rr_parents_catalog_idx');
        });

        Schema::table('rr_materials', function (Blueprint $table) {
            $table->dropIndex(['material_parent_id']);
            $table->dropIndex(['deleted_at']);
            $table->dropIndex('rr_materials_availability_idx');
        });

        Schema::table('material_access_events', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropIndex(['rr_material_id']);
            $table->dropIndex(['approver_id']);
            $table->dropIndex(['status']);
            $table->dropIndex(['event_type']);
            $table->dropIndex(['due_at']);
            $table->dropIndex(['deleted_at']);
            $table->dropIndex('mae_user_type_status_idx');
            $table->dropIndex('mae_due_soon_idx');
            $table->dropIndex('mae_overdue_idx');
        });

        Schema::table('repository_change_logs', function (Blueprint $table) {
            $table->dropIndex(['editor_id']);
            $table->dropIndex(['rr_material_id']);
            $table->dropIndex(['target_user_id']);
            $table->dropIndex(['change_type']);
            $table->dropIndex(['table_changed']);
            $table->dropIndex(['changed_at']);
            $table->dropIndex(['deleted_at']);
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex(['read_at']);
            $table->dropIndex(['created_at']);
            $table->dropIndex('notifications_unread_idx');
        });
    }
};
