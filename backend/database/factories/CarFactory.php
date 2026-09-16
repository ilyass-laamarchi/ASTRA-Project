<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/** Generates operational vehicles with plausible Moroccan rental data. */
class CarFactory extends Factory
{
    /** Returns an active, available vehicle with a related category. */
    public function definition(): array
    {
        return ['category_id' => Category::factory(), 'registration_number' => 'TEST-'.fake()->unique()->numerify('#####'), 'brand' => fake()->randomElement(['Renault', 'Peugeot', 'Dacia', 'Hyundai']), 'model' => fake()->randomElement(['Clio', '208', 'Duster', 'Tucson']), 'year' => fake()->numberBetween(2021, 2026), 'color' => 'Bleu', 'seats' => 5, 'doors' => 5, 'fuel_type' => 'gasoline', 'transmission' => 'automatic', 'daily_price' => fake()->numberBetween(350, 900), 'mileage' => fake()->numberBetween(5000, 70000), 'description' => fake()->sentence(), 'operational_status' => 'available', 'is_active' => true];
    }
}
