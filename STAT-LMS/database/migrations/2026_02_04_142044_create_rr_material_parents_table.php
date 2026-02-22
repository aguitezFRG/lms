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
        Schema::create('rr_material_parents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->integer('material_type');
            $table->string('title');
            $table->string('abstract');
            $table->string('keywords');
            $table->string('sdgs')->nullable();
            $table->date('publication_date');
            $table->foreignUuid('author')->constrained('users');
            $table->json('adviser')->nullable();
            $table->integer('access_level');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rr_material_parents');
    }
};
