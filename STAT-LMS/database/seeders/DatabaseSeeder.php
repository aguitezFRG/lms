<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Create your mandatory Super Admin
        \App\Models\User::factory()->superAdmin()->create();

        // 2. Create 10 random users for testing
        \App\Models\User::factory(10)->create();
    }
}
