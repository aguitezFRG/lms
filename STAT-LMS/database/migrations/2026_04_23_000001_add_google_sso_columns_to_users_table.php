<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('google_id')->nullable()->unique()->after('email');
            $table->boolean('is_profile_complete')->default(false)->after('google_id');
        });

        // Existing users are already enrolled — mark all as complete.
        // Use DB::table to bypass observers (avoids audit log noise for this system migration).
        DB::table('users')->update(['is_profile_complete' => true]);
    }

    public function rollback(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['google_id']);
            $table->dropColumn(['google_id', 'is_profile_complete']);
        });
    }
};
