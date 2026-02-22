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
        Schema::create('rr_materials', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('material_parent_id')->constrained('rr_material_parents');
            $table->boolean('is_digital');
            $table->boolean('is_available');
            $table->string('file_name');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rr_materials');
    }
};
