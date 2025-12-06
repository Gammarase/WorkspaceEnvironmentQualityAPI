<?php

namespace Database\Factories;

use App\Models\Device;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class RecommendationFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'device_id' => Device::factory(),
            'user_id' => User::factory(),
            'type' => fake()->randomElement(['ventilate', 'lighting', 'noise', 'break', 'temperature', 'humidity']),
            'title' => fake()->sentence(4),
            'message' => fake()->text(),
            'priority' => fake()->randomElement(['low', 'medium', 'high']),
            'status' => fake()->randomElement(['pending', 'acknowledged', 'dismissed']),
            'metadata' => [fake()->word() => fake()->word()],
            'acknowledged_at' => fake()->dateTime(),
            'dismissed_at' => fake()->dateTime(),
        ];
    }
}
