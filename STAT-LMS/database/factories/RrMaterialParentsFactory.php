<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\rr_material_parents>
 */
class RrMaterialParentsFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'material_type' => fake()->randomElement(['Book', 'Journal', 'Thesis', 'Dataset', 'Research Paper']),
            'title' => fake()->sentence(6),
            'abstract' => fake()->paragraph(3),
            'keywords' => implode(', ', fake()->words(5)),
            'sdgs' => implode(', ', fake()->words(3)),
            'publication_date' => fake()->dateTimeBetween('-5 years', 'now'),
            'author' => fake()->name(),
            'adviser' => json_encode([fake()->name(), fake()->name()]),
            'access_level' => fake()->randomElement(['Public', 'Restricted', 'Confidential']),
        ];
    }
}
