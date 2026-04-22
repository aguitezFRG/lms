<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /*
     * Pre-determined UUIDs for specific seed users
     *
     * Pattern: 22222222-2222-2222-2222-0000000000{nn}
     */
    public const STUDENT_1_ID = '22222222-2222-2222-2222-000000000001';

    public const STUDENT_2_ID = '22222222-2222-2222-2222-000000000002';

    public const STUDENT_3_ID = '22222222-2222-2222-2222-000000000003';

    public const FACULTY_1_ID = '22222222-2222-2222-2222-000000000004';

    public const FACULTY_2_ID = '22222222-2222-2222-2222-000000000005';

    public const STAFF_ID = '22222222-2222-2222-2222-000000000006';

    public const COMMITTEE_ID = '22222222-2222-2222-2222-000000000007';

    public const IT_ID = '22222222-2222-2222-2222-000000000008';

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // ── Create specific seed users with predetermined UUIDs ─────────────────
        User::factory()->create([
            'id' => self::STUDENT_1_ID,
            'f_name' => 'Carlos',
            'l_name' => 'Santos',
            'role' => 'student',
        ]);
        User::factory()->create([
            'id' => self::STUDENT_2_ID,
            'f_name' => 'Angelica',
            'l_name' => 'Reyes',
            'role' => 'student',
        ]);
        User::factory()->create([
            'id' => self::STUDENT_3_ID,
            'f_name' => 'Rafael',
            'l_name' => 'Cruz',
            'role' => 'student',
        ]);
        User::factory()->create([
            'id' => self::FACULTY_1_ID,
            'f_name' => 'Ricardo',
            'l_name' => 'Mendoza',
            'role' => 'faculty',
        ]);
        User::factory()->create([
            'id' => self::FACULTY_2_ID,
            'f_name' => 'Esperanza',
            'l_name' => 'Garcia',
            'role' => 'faculty',
        ]);
        User::factory()->create([
            'id' => self::STAFF_ID,
            'f_name' => 'Staff',
            'l_name' => 'Custodian',
            'role' => 'staff/custodian',
        ]);
        User::factory()->create([
            'id' => self::COMMITTEE_ID,
            'f_name' => 'Committee',
            'l_name' => 'Member',
            'role' => 'committee',
        ]);
        User::factory()->create([
            'id' => self::IT_ID,
            'f_name' => 'IT',
            'l_name' => 'Support',
            'role' => 'it',
        ]);

        // ── Additional random users for a fuller dataset ───────────────────────
        User::factory(8)->create(['role' => 'student']);
        User::factory(3)->create(['role' => 'faculty']);
    }
}
