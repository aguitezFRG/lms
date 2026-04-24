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
    use UserRoleStates;

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
                    $attributes['l_name'],
                ])));
            },
            'std_number' => fake()->numberBetween(1908, 2025).'-'.fake()->numerify('#####'),
            'revoked_at' => null,
            'is_profile_complete' => true,
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn () => [
            'email_verified_at' => null,
        ]);
    }

    public function sso(): static
    {
        return $this->state(fn () => [
            'google_id' => fake()->unique()->numerify('############'),
            'is_profile_complete' => true,
        ]);
    }

    public function incompleteProfile(): static
    {
        return $this->state(fn () => [
            'is_profile_complete' => false,
            'f_name' => null,
            'l_name' => null,
            'm_name' => null,
            'std_number' => null,
        ]);
    }
}
