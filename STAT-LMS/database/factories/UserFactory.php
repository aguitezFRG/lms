<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => Str::uuid(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'role' => fake()->randomElement(['student', 'faculty', 'staff/custodian', 'it', 'committee']),
            'f_name' => fake()->firstName(),
            'm_name' => fake()->firstName(),
            'l_name' => fake()->lastName(),
            'name' => function (array $attributes) {
                return trim(implode(' ', array_filter([
                    $attributes['f_name'],
                    $attributes['m_name'],
                    $attributes['l_name']
                ])));
            },
            'std_number' => fake()->numberBetween(1908, 2025) . '-' . fake()->numerify('#####'),
            'revoked_at' => null,
        ];
    }

    public function superAdmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'f_name' => 'super',
            'm_name' => null,
            'l_name' => 'admin',
            'name' => 'super admin',
            'email' => 'admin@gmail.com',
            'password' => Hash::make('ADMINpass@1234'),
            'role' => 'committee',
            'std_number' => null,
        ]);
    }

    public function it(): static
    {
        return $this->state(fn (array $attributes) => [
            'f_name' => 'IT',
            'm_name' => null,
            'l_name' => 'Support',
            'name' => 'IT Support',
            'email' => 'it.support@gmail.com',
            'password' => Hash::make('ITpass@1234'),
            'role' => 'it',
            'std_number' => null,
        ]);
    }

    public function staff(): static
    {
        return $this->state(fn (array $attributes) => [
            'f_name' => 'Staff',
            'm_name' => null,
            'l_name' => 'Custodian',
            'name' => 'Staff Custodian',
            'email' => 'staff.custodian@gmail.com',
            'password' => Hash::make('STAFFpass@1234'),
            'role' => 'staff/custodian',
            'std_number' => null,
        ]);
    }

    public function faculty(): static
    {
        return $this->state(fn (array $attributes) => [
            'f_name' => 'Faculty',
            'm_name' => null,
            'l_name' => 'Member',
            'name' => 'Faculty Member',
            'email' => 'faculty.member@gmail.com',
            'password' => Hash::make('FACULTYpass@1234'),
            'role' => 'faculty',
            'std_number' => null,
        ]);
    }

    public function student(): static
    {
        return $this->state(fn (array $attributes) => [
            'f_name' => 'Student',
            'm_name' => null,
            'l_name' => 'User',
            'name' => 'Student User',
            'email' => 'student.user@gmail.com',
            'password' => Hash::make('STUDENTpass@1234'),
            'role' => 'student',
            'std_number' => fake()->numberBetween(1908, 2025) . '-' . fake()->numerify('#####'),
        ]);
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
