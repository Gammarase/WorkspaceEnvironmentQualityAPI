<?php

namespace Database\Seeders;

use App\Models\ExternalWeatherData;
use Illuminate\Database\Seeder;

class ExternalWeatherDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ExternalWeatherData::factory()->count(5)->create();
    }
}
