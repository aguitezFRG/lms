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
        Schema::create('material_access_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users');
            $table->foreignUuid('rr_material_id')->constrained('rr_materials');
            $table->foreignUuid('approver_id')->constrained('users');
            $table->string('event_type');
            $table->string('status');
            $table->timestamp('due_at')->nullable();
            $table->timestamp('returned_at')->nullable();
            $table->boolean('is_overdue')->default(false);
            $table->timestamps();
            $table->timestamp('approved_at')->nullable()->default(time());
            $table->timestamp('completed_at')->nullable()->default(time());
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('material_access_events');
    }
};
