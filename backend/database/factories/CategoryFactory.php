<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Generates fleet categories for isolated feature tests. */
class CategoryFactory extends Factory
{
    /** Returns an active unique category suitable for fleet tests. */
    public function definition(): array
    {
        return ['name' => fake()->unique()->randomElement(['Citadine', 'Berline', 'SUV', 'Prestige']).' '.fake()->unique()->numberBetween(1, 999), 'description' => fake()->sentence(), 'is_active' => true];
    }
}
