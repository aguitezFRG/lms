<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const INDEX_NAME = 'material_access_events_one_active_borrow_per_copy';

    public function up(): void
    {
        DB::statement(sprintf(
            "CREATE UNIQUE INDEX %s ON material_access_events (rr_material_id) WHERE event_type = 'borrow' AND status = 'approved' AND returned_at IS NULL AND deleted_at IS NULL",
            self::INDEX_NAME,
        ));
    }

    public function down(): void
    {
        DB::statement(sprintf('DROP INDEX IF EXISTS %s', self::INDEX_NAME));
    }
};
