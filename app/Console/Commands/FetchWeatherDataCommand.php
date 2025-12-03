<?php

namespace App\Console\Commands;

use App\Models\Device;
use App\Models\ExternalWeatherData;
use App\Services\WeatherService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FetchWeatherDataCommand extends Command
{
    protected $signature = 'weather:fetch';

    protected $description = 'Fetch weather data for all unique device locations';

    public function __construct(protected WeatherService $weatherService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $locations = Device::query()
            ->where('is_active', true)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->selectRaw('ROUND(latitude::numeric, 2) as lat_rounded, ROUND(longitude::numeric, 2) as lng_rounded')
            ->groupBy(DB::raw('ROUND(latitude::numeric, 2), ROUND(longitude::numeric, 2)'))
            ->get();

        if ($locations->isEmpty()) {
            $this->info('No active devices with location data found.');

            return self::SUCCESS;
        }

        $fetched = 0;
        $skipped = 0;

        foreach ($locations as $location) {
            $lat = (float) $location->lat_rounded;
            $lng = (float) $location->lng_rounded;

            $existingWeather = ExternalWeatherData::query()
                ->whereBetween('latitude', [$lat - 0.01, $lat + 0.01])
                ->whereBetween('longitude', [$lng - 0.01, $lng + 0.01])
                ->where('fetched_at', '>=', now()->subHour())
                ->first();

            if ($existingWeather) {
                $skipped++;

                continue;
            }

            $weatherData = $this->weatherService->fetchWeatherForLocation($lat, $lng);

            if ($weatherData) {
                ExternalWeatherData::create($weatherData);
                $fetched++;
                $this->info("Fetched weather for location ({$lat}, {$lng})");
            } else {
                $this->warn("Failed to fetch weather for location ({$lat}, {$lng})");
            }
        }

        $deviceCount = Device::query()
            ->where('is_active', true)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->count();

        $this->info("Completed: Fetched {$fetched} new weather records, skipped {$skipped} (recent data exists) for {$deviceCount} active devices.");

        return self::SUCCESS;
    }
}
