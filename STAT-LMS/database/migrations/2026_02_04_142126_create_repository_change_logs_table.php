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
            $table->foreignUuid('rr_material_id')->constrained('rr_materials')->nullable();
            $table->foreignUuid('target_user_id')->constrained('users')->nullable();
            $table->string('table_changed');
            $table->string('change_type');
            $table->json('change_made')->nullable();
            $table->timestamp('changed_at')->default(time());
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
