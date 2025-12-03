<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DeviceFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'device_id' => fake()->regexify('[A-Za-z0-9]{100}'),
            'user_id' => User::factory(),
            'name' => fake()->name(),
            'latitude' => fake()->latitude(),
            'longitude' => fake()->longitude(),
            'description' => fake()->text(),
            'is_active' => fake()->boolean(),
            'last_seen_at' => fake()->dateTime(),
        ];
    }
}
