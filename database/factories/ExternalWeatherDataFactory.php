<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ExternalWeatherDataFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'location' => fake()->regexify('[A-Za-z0-9]{255}'),
            'latitude' => fake()->latitude(),
            'longitude' => fake()->longitude(),
            'outdoor_temperature' => fake()->randomFloat(2, 0, 999.99),
            'outdoor_humidity' => fake()->randomFloat(2, 0, 999.99),
            'outdoor_aqi' => fake()->randomNumber(),
            'outdoor_pm25' => fake()->randomFloat(2, 0, 9999.99),
            'outdoor_pm10' => fake()->randomFloat(2, 0, 9999.99),
            'weather_condition' => fake()->regexify('[A-Za-z0-9]{100}'),
            'source' => fake()->randomElement(['openweathermap', 'airvisual', 'iqair']),
            'fetched_at' => fake()->dateTime(),
        ];
    }
}
