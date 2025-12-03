<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WeatherService
{
    protected ?string $apiKey;

    protected string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.weatherapi.api_key');
        $this->baseUrl = config('services.weatherapi.base_url');
    }

    public function fetchWeatherForLocation(float $latitude, float $longitude): ?array
    {
        if (! $this->apiKey) {
            Log::warning('WeatherAPI key not configured');

            return null;
        }

        try {
            $response = Http::timeout(10)->get("{$this->baseUrl}/current.json", [
                'key' => $this->apiKey,
                'q' => "{$latitude},{$longitude}",
                'aqi' => 'yes',
            ]);

            if (! $response->successful()) {
                Log::error('WeatherAPI request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                ]);

                return null;
            }

            $data = $response->json();

            return [
                'location' => $data['location']['name'] ?? 'Unknown',
                'latitude' => $latitude,
                'longitude' => $longitude,
                'outdoor_temperature' => $data['current']['temp_c'] ?? null,
                'outdoor_humidity' => $data['current']['humidity'] ?? null,
                'outdoor_aqi' => $data['current']['air_quality']['us-epa-index'] ?? null,
                'outdoor_pm25' => $data['current']['air_quality']['pm2_5'] ?? null,
                'outdoor_pm10' => $data['current']['air_quality']['pm10'] ?? null,
                'weather_condition' => $data['current']['condition']['text'] ?? null,
                'source' => 'weatherapi',
                'fetched_at' => now(),
            ];
        } catch (\Exception $e) {
            Log::error('WeatherAPI exception', [
                'message' => $e->getMessage(),
                'latitude' => $latitude,
                'longitude' => $longitude,
            ]);

            return null;
        }
    }

    public function getOrFetchWeather(float $latitude, float $longitude): ?array
    {
        $roundedLat = round($latitude, 2);
        $roundedLng = round($longitude, 2);
        $cacheKey = "weather:{$roundedLat}:{$roundedLng}";

        return Cache::remember($cacheKey, 3600, function () use ($latitude, $longitude) {
            return $this->fetchWeatherForLocation($latitude, $longitude);
        });
    }
}
