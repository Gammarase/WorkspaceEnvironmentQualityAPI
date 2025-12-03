<?php

namespace Database\Seeders;

use App\Models\SensorReading;
use Illuminate\Database\Seeder;

class SensorReadingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        SensorReading::factory()->count(5)->create();
    }
}
