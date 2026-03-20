<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RrMaterialParents>
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
            'keywords'         => ['stats', 'research'],           // plain array — model casts handle JSON
            'sdgs'             => ['Quality Education'],            // plain array
            'publication_date' => fake()->dateTimeBetween('-5 years', 'now'),
            'author'           => fake()->name(),
            'adviser'          => ['Test Adviser'],                 // plain array
            'access_level'     => fake()->randomElement([1, 2, 3]), // use int keys
        ];
    }
}
