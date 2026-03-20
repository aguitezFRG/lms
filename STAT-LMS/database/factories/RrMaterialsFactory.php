<?php

namespace Database\Factories;

use App\Models\RrMaterialParents;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RrMaterials>
 */
class RrMaterialsFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'material_parent_id' => RrMaterialParents::factory(),
            'is_digital' => fake()->boolean(70),
            'is_available' => fake()->boolean(80),
            'file_name' => fake()->word() . '_' . $this->faker->numberBetween(1000, 9999) . '-' . $this->faker->numerify('#####') . '.' . fake()->randomElement(['pdf', 'docx', 'xlsx', 'txt']),
        ];
    }
}
