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
        User::factory()->superAdmin()->create();

        // 2. Create 10 random users for testing
        User::factory(10)->create();

        // 3. Create specific users with known credentials for testing
        User::factory()->committee()->create();
        User::factory()->it()->create();
        User::factory()->staff()->create();
        User::factory()->faculty()->create();
        User::factory()->student()->create();

        $this->call([
            UserSeeder::class,
            RrMaterialParentsSeeder::class,
            RrMaterialsSeeder::class,
            MaterialAccessEventsSeeder::class,
        ]);
    }
}
