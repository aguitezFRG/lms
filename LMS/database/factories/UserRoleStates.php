<?php

namespace Database\Factories;

use Illuminate\Support\Facades\Hash;

trait UserRoleStates
{
    public function superAdmin(): static
    {
        return $this->state(fn () => [
            'f_name' => 'Super',
            'm_name' => null,
            'l_name' => 'Admin',
            'email' => 'admin@gmail.com',
            'password' => Hash::make('Sup3rAdm!n#2025'),
            'role' => 'super_admin',
            'std_number' => null,
        ]);
    }

    public function committee(): static
    {
        return $this->state(fn () => [
            'f_name' => 'Committee',
            'm_name' => null,
            'l_name' => 'Member',
            'email' => 'committee.member@gmail.com',
            'password' => Hash::make('C0mm!tt33#2025'),
            'role' => 'committee',
            'std_number' => null,
        ]);
    }

    public function it(): static
    {
        return $this->state(fn () => [
            'f_name' => 'IT',
            'm_name' => null,
            'l_name' => 'Support',
            'email' => 'it.support@gmail.com',
            'password' => Hash::make('1Tsupport#2025'),
            'role' => 'it',
            'std_number' => null,
        ]);
    }

    public function staff(): static
    {
        return $this->state(fn () => [
            'f_name' => 'Staff',
            'm_name' => null,
            'l_name' => 'Custodian',
            'email' => 'staff.custodian@gmail.com',
            'password' => Hash::make('St4ffCust0d#2025'),
            'role' => 'staff/custodian',
            'std_number' => null,
        ]);
    }

    public function faculty(): static
    {
        return $this->state(fn () => [
            'f_name' => 'Faculty',
            'm_name' => null,
            'l_name' => 'Member',
            'email' => 'faculty.member@gmail.com',
            'password' => Hash::make('F4cu1tyM3mb#2025'),
            'role' => 'faculty',
            'std_number' => null,
        ]);
    }

    public function student(): static
    {
        return $this->state(fn () => [
            'f_name' => 'Student',
            'm_name' => null,
            'l_name' => 'User',
            'email' => 'student.user@gmail.com',
            'password' => Hash::make('Stud3ntUs3r#2025'),
            'role' => 'student',
            'std_number' => fake()->numberBetween(1908, 2025).'-'.fake()->numerify('#####'),
        ]);
    }
}
