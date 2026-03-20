<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\RrMaterials;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\material_access_events>
 */
class MaterialAccessEventsFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'rr_material_id' => RrMaterials::factory(),
            'approver_id' => User::factory(),
            'event_type' => fake()->randomElement(['Borrow', 'Return', 'Request', 'Approval']),
            'status' => fake()->randomElement(['Pending', 'Approved', 'Rejected', 'Completed']),
            'due_at' => fake()->dateTimeBetween('now', '+30 days'),
            'returned_at' => fake()->optional(0.6)->dateTime(),
            'is_overdue' => fake()->boolean(20),
            'approved_at' => fake()->dateTime(),
            'completed_at' => fake()->optional(0.5)->dateTime(),
        ];
    }
}
