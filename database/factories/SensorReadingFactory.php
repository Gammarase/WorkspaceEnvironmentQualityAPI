<?php

namespace Database\Factories;

use App\Models\Device;
use Illuminate\Database\Eloquent\Factories\Factory;

class SensorReadingFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'device_id' => Device::factory(),
            'temperature' => fake()->randomFloat(2, 0, 999.99),
            'humidity' => fake()->randomFloat(2, 0, 999.99),
            'tvoc_ppm' => fake()->randomNumber(),
            'light' => fake()->randomNumber(),
            'noise' => fake()->randomNumber(),
            'reading_timestamp' => fake()->dateTime(),
        ];
    }
}
