<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/** Produces safe client accounts for tests; staff roles are explicit in seeders. */
class UserFactory extends Factory
{
    protected static ?string $password;

    /** Returns a valid active client record for isolated tests. */
    public function definition(): array
    {
        return ['first_name' => fake()->firstName(), 'last_name' => fake()->lastName(), 'email' => fake()->unique()->safeEmail(), 'phone' => fake()->phoneNumber(), 'password' => static::$password ??= Hash::make('Password123!'), 'role' => 'client', 'is_active' => true];
    }

    /** Returns a factory state for testing disabled-account behavior. */
    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
