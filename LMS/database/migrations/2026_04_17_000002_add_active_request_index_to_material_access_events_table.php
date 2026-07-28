<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('material_access_events', function (Blueprint $table) {
            $table->index(['user_id', 'rr_material_id', 'event_type', 'status'], 'mae_active_request_idx');
        });
    }

    public function down(): void
    {
        Schema::table('material_access_events', function (Blueprint $table) {
            $table->dropIndex('mae_active_request_idx');
        });
    }
};
