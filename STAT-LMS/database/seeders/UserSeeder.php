<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // ── Additional random users for a fuller dataset ───────────────────────
        User::factory(8)->create(['role' => 'student']);
        User::factory(3)->create(['role' => 'faculty']);
    }
}
