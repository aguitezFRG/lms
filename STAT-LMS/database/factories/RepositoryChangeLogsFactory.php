<?php

namespace Database\Factories;

use App\Models\RrMaterials;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\repository_change_logs>
 */
class RepositoryChangeLogsFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'editor_id' => User::factory(),
            'rr_material_id' => fake()->optional(0.7)->randomElement(RrMaterials::pluck('id')->toArray()) ?? RrMaterials::factory(),
            'target_user_id' => fake()->optional(0.5)->randomElement(User::pluck('id')->toArray()) ?? User::factory(),
            'table_changed' => fake()->randomElement(['rr_materials', 'rr_material_parents', 'material_access_events']),
            'change_type' => fake()->randomElement(['Create', 'Update', 'Delete', 'Restore']),
            'change_made' => json_encode([
                'field' => fake()->word(),
                'old_value' => fake()->word(),
                'new_value' => fake()->word(),
            ]),
            'changed_at' => fake()->dateTime(),
        ];
    }
}
