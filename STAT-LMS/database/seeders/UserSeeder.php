<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /*
     * Pre-determined UUIDs — reference these from other seeders.
     *
     * Pattern: 11111111-1111-1111-1111-0000000000{nn}
     */
    public const COMMITTEE_ID = '11111111-1111-1111-1111-000000000001';

    public const IT_ID = '11111111-1111-1111-1111-000000000002';

    public const STAFF_ID = '11111111-1111-1111-1111-000000000003';

    public const FACULTY_1_ID = '11111111-1111-1111-1111-000000000004'; // Ricardo Garcia

    public const FACULTY_2_ID = '11111111-1111-1111-1111-000000000005'; // Esperanza Villanueva

    public const STUDENT_1_ID = '11111111-1111-1111-1111-000000000006'; // Carlos Santos

    public const STUDENT_2_ID = '11111111-1111-1111-1111-000000000007'; // Angelica Mendoza

    public const STUDENT_3_ID = '11111111-1111-1111-1111-000000000008'; // Rafael Torres

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // ── Named / Role-specific Users (fixed UUIDs) ─────────────────────────
        $named = [
            [
                'id' => self::COMMITTEE_ID,
                'f_name' => 'Maria Luisa',
                'm_name' => 'Santos',
                'l_name' => 'Reyes',
                'name' => 'Maria Luisa Santos Reyes',
                'email' => 'mlsreyes@uplb.edu.ph',
                'password' => Hash::make('Committee@1234'),
                'role' => 'committee',
                'std_number' => null,
            ],
            [
                'id' => self::IT_ID,
                'f_name' => 'Jose Antonio',
                'm_name' => 'Cruz',
                'l_name' => 'Bautista',
                'name' => 'Jose Antonio Cruz Bautista',
                'email' => 'jacbautista@uplb.edu.ph',
                'password' => Hash::make('ITAdmin@1234'),
                'role' => 'it',
                'std_number' => null,
            ],
            [
                'id' => self::STAFF_ID,
                'f_name' => 'Ana Marie',
                'm_name' => 'dela',
                'l_name' => 'Cruz',
                'name' => 'Ana Marie dela Cruz',
                'email' => 'amdelacruz@uplb.edu.ph',
                'password' => Hash::make('Staff@1234'),
                'role' => 'staff/custodian',
                'std_number' => null,
            ],
            [
                'id' => self::FACULTY_1_ID,
                'f_name' => 'Ricardo',
                'm_name' => 'Manuel',
                'l_name' => 'Garcia',
                'name' => 'Ricardo Manuel Garcia',
                'email' => 'rmgarcia@uplb.edu.ph',
                'password' => Hash::make('Faculty@1234'),
                'role' => 'faculty',
                'std_number' => null,
            ],
            [
                'id' => self::FACULTY_2_ID,
                'f_name' => 'Esperanza',
                'm_name' => 'Luz',
                'l_name' => 'Villanueva',
                'name' => 'Esperanza Luz Villanueva',
                'email' => 'elvillanueva@uplb.edu.ph',
                'password' => Hash::make('Faculty@1234'),
                'role' => 'faculty',
                'std_number' => null,
            ],
            [
                'id' => self::STUDENT_1_ID,
                'f_name' => 'Carlos',
                'm_name' => 'Miguel',
                'l_name' => 'Santos',
                'name' => 'Carlos Miguel Santos',
                'email' => '2021-12345@uplb.edu.ph',
                'password' => Hash::make('Student@1234'),
                'role' => 'student',
                'std_number' => '2021-12345',
            ],
            [
                'id' => self::STUDENT_2_ID,
                'f_name' => 'Angelica',
                'm_name' => 'Flores',
                'l_name' => 'Mendoza',
                'name' => 'Angelica Flores Mendoza',
                'email' => '2020-54321@uplb.edu.ph',
                'password' => Hash::make('Student@1234'),
                'role' => 'student',
                'std_number' => '2020-54321',
            ],
            [
                'id' => self::STUDENT_3_ID,
                'f_name' => 'Rafael',
                'm_name' => 'Jose',
                'l_name' => 'Torres',
                'name' => 'Rafael Jose Torres',
                'email' => '2022-67890@uplb.edu.ph',
                'password' => Hash::make('Student@1234'),
                'role' => 'student',
                'std_number' => '2022-67890',
            ],
        ];

        foreach ($named as $data) {
            User::factory()->create($data);
        }

        // ── Additional random users for a fuller dataset ───────────────────────
        User::factory(8)->create(['role' => 'student']);
        User::factory(3)->create(['role' => 'faculty']);
    }
}
