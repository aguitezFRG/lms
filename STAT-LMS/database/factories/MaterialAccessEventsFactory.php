<?php

namespace Database\Factories;

use App\Models\RrMaterials;
use App\Models\User;
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
        $status = fake()->randomElement(['pending', 'approved', 'rejected', 'completed', 'cancelled']);

        return [
            'user_id' => User::factory(),
            'rr_material_id' => RrMaterials::factory(),
            'approver_id' => in_array($status, ['approved', 'rejected', 'completed'])
                ? User::factory()
                : null,
            'event_type' => fake()->randomElement(['request', 'borrow']),
            'status' => $status,
            'due_at' => in_array($status, ['approved', 'completed'])
                ? fake()->dateTimeBetween('now', '+30 days')
                : null,
            'returned_at' => $status === 'completed' ? fake()->dateTimeBetween('-10 days', 'now') : null,
            'is_overdue' => false,
            'approved_at' => in_array($status, ['approved', 'completed']) ? now() : null,
            'completed_at' => $status === 'completed' ? now() : null,
        ];
    }
}
