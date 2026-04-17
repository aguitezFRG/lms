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
        $isDigital = fake()->boolean(70);

        return [
            'material_parent_id' => RrMaterialParents::factory(),
            'is_digital' => $isDigital,
            'is_available' => fake()->boolean(80),
            'file_name' => $isDigital
                ? 'repository/access_level_1/'
                    .fake()->randomElement(['book', 'thesis', 'journal', 'dissertation', 'other'])
                    .'_'.fake()->slug(4)
                    .'-'.fake()->year()
                    .'-'.\Illuminate\Support\Str::uuid()
                    .'-v1.pdf'
                : null,
        ];
    }
}
