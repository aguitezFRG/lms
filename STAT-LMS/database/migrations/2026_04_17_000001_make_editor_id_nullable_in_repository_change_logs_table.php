<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('repository_change_logs', function (Blueprint $table) {
            $table->foreignUuid('editor_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('repository_change_logs', function (Blueprint $table) {
            $table->foreignUuid('editor_id')->nullable(false)->change();
        });
    }
};
