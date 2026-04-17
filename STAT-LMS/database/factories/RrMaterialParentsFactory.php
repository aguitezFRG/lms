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
            'material_type' => fake()->randomElement([1, 2, 3, 4, 5]),
            'title' => fake()->sentence(6),
            'abstract' => fake()->paragraph(3),
            'keywords' => ['stats', 'research'],
            'sdgs' => ['Quality Education'],
            'publication_date' => fake()->dateTimeBetween('-5 years', 'now'),
            'author' => fake()->name(),
            'adviser' => ['Test Adviser'],
            'access_level' => fake()->randomElement([1, 2, 3]),
        ];
    }
}
