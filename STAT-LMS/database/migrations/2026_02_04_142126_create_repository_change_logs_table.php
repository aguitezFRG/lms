<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('repository_change_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('editor_id')->constrained('users');
            $table->foreignUuid('material_parent_id')->nullable()->constrained('rr_material_parents');
            $table->foreignUuid('rr_material_id')->nullable()->constrained('rr_materials');
            $table->foreignUuid('target_user_id')->nullable()->constrained('users');
            $table->string('table_changed');
            $table->string('change_type');
            $table->json('change_made')->nullable();
            $table->timestamp('changed_at')->default(now()); // changed time() to now() --- IGNORE ---
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('repository_change_logs');
    }
};
