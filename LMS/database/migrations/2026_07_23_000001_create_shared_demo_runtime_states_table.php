<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shared_demo_runtime_states', function (Blueprint $table): void {
            $table->unsignedTinyInteger('id')->primary();
            $table->string('status', 32)->default('ready');
            $table->boolean('maintenance')->default(false);
            $table->string('last_idempotency_key', 191)->nullable();
            $table->timestamp('last_bootstrapped_at')->nullable();
            $table->timestamp('last_reset_at')->nullable();
            $table->unsignedInteger('last_duration_ms')->nullable();
            $table->json('canonical_counts')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shared_demo_runtime_states');
    }
};
