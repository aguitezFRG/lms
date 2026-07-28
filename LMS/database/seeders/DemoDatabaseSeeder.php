<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DemoDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        Carbon::setTestNow('2026-07-22 09:00:00');

        DB::table('users')->insert($this->profiles());

        $this->call([
            RrMaterialParentsSeeder::class,
            RrMaterialsSeeder::class,
            MaterialAccessEventsSeeder::class,
        ]);

        Carbon::setTestNow();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function profiles(): array
    {
        $timestamp = '2026-07-22 09:00:00';
        $password = '$2y$04$usesomesillystringfore7hnbRJHxXVLeakoG8K30oukPsA.ztMG';

        return [
            $this->profile(UserSeeder::STUDENT_1_ID, 'Carlos Santos', 'Carlos', 'Santos', 'carlos.student@demo.lms', 'student', '2024-00001', $password, $timestamp),
            $this->profile(UserSeeder::FACULTY_1_ID, 'Ricardo Mendoza', 'Ricardo', 'Mendoza', 'ricardo.faculty@demo.lms', 'faculty', '2008-00004', $password, $timestamp),
            $this->profile(UserSeeder::STAFF_ID, 'Staff Custodian', 'Staff', 'Custodian', 'custodian@demo.lms', 'staff/custodian', null, $password, $timestamp),
            $this->profile(UserSeeder::COMMITTEE_ID, 'Committee Member', 'Committee', 'Member', 'committee@demo.lms', 'committee', null, $password, $timestamp),
            $this->profile(UserSeeder::IT_ID, 'IT Support', 'IT', 'Support', 'it@demo.lms', 'it', null, $password, $timestamp),
            $this->profile('22222222-2222-2222-2222-000000000009', 'Super Admin', 'Super', 'Admin', 'super-admin@demo.lms', 'super_admin', null, $password, $timestamp),
            $this->profile(UserSeeder::STUDENT_2_ID, 'Angelica Reyes', 'Angelica', 'Reyes', 'angelica.student@demo.lms', 'student', '2024-00002', $password, $timestamp),
            $this->profile(UserSeeder::STUDENT_3_ID, 'Rafael Cruz', 'Rafael', 'Cruz', 'rafael.student@demo.lms', 'student', '2024-00003', $password, $timestamp),
            $this->profile(UserSeeder::FACULTY_2_ID, 'Esperanza Garcia', 'Esperanza', 'Garcia', 'esperanza.faculty@demo.lms', 'faculty', '2008-00005', $password, $timestamp),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function profile(
        string $id,
        string $name,
        string $firstName,
        string $lastName,
        string $email,
        string $role,
        ?string $number,
        string $password,
        string $timestamp,
    ): array {
        return [
            'id' => $id,
            'name' => $name,
            'email' => $email,
            'email_verified_at' => $timestamp,
            'google_id' => null,
            'is_profile_complete' => true,
            'password' => $password,
            'role' => $role,
            'f_name' => $firstName,
            'm_name' => null,
            'l_name' => $lastName,
            'std_number' => $number,
            'is_banned' => false,
            'revoked_at' => null,
            'remember_token' => null,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
            'deleted_at' => null,
        ];
    }
}
