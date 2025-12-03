<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'email' => fake()->safeEmail(),
            'name' => fake()->name(),
            'password' => fake()->password(),
            'timezone' => fake()->regexify('[A-Za-z0-9]{50}'),
            'language' => fake()->randomElement(['uk', 'en']),
        ];
    }
}
