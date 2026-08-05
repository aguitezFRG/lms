<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const INDEX_NAME = 'material_access_events_one_active_borrow_per_copy';

    public function up(): void
    {
        DB::transaction(function (): void {
            $duplicateCopyIds = DB::table('material_access_events')
                ->select('rr_material_id')
                ->where('event_type', 'borrow')
                ->where('status', 'approved')
                ->whereNull('returned_at')
                ->whereNull('deleted_at')
                ->groupBy('rr_material_id')
                ->havingRaw('COUNT(*) > 1')
                ->pluck('rr_material_id');

            foreach ($duplicateCopyIds as $copyId) {
                $conflictingIds = DB::table('material_access_events')
                    ->where('rr_material_id', $copyId)
                    ->where('event_type', 'borrow')
                    ->where('status', 'approved')
                    ->whereNull('returned_at')
                    ->whereNull('deleted_at')
                    ->orderByRaw('approved_at IS NULL')
                    ->orderBy('approved_at')
                    ->orderBy('created_at')
                    ->orderBy('id')
                    ->pluck('id')
                    ->slice(1)
                    ->values();

                if ($conflictingIds->isEmpty()) {
                    continue;
                }

                DB::table('material_access_events')
                    ->whereIn('id', $conflictingIds)
                    ->update([
                        'status' => 'pending',
                        'approver_id' => null,
                        'approved_at' => null,
                        'due_at' => null,
                        'completed_at' => null,
                        'updated_at' => now(),
                    ]);
            }

            DB::statement(sprintf(
                "CREATE UNIQUE INDEX %s ON material_access_events (rr_material_id) WHERE event_type = 'borrow' AND status = 'approved' AND returned_at IS NULL AND deleted_at IS NULL",
                self::INDEX_NAME,
            ));
        });
    }

    public function down(): void
    {
        DB::statement(sprintf('DROP INDEX IF EXISTS %s', self::INDEX_NAME));
    }
};
