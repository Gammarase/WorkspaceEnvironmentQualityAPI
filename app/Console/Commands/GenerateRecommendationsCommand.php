<?php

namespace App\Console\Commands;

use App\Models\Device;
use App\Models\ExternalWeatherData;
use App\Models\Recommendation;
use App\Models\SensorReading;
use Illuminate\Console\Command;

class GenerateRecommendationsCommand extends Command
{
    protected $signature = 'recommendations:generate';

    protected $description = 'Generate recommendations based on sensor readings and weather data';

    public function handle(): int
    {
        $devices = Device::where('is_active', true)->get();

        if ($devices->isEmpty()) {
            $this->info('No active devices found.');

            return self::SUCCESS;
        }

        $generated = 0;

        foreach ($devices as $device) {
            $latestReading = SensorReading::where('device_id', $device->id)
                ->where('reading_timestamp', '>=', now()->subMinutes(10))
                ->latest('reading_timestamp')
                ->first();

            if (! $latestReading) {
                continue;
            }

            $weather = null;
            if ($device->latitude && $device->longitude) {
                $weather = ExternalWeatherData::query()
                    ->whereBetween('latitude', [$device->latitude - 0.05, $device->latitude + 0.05])
                    ->whereBetween('longitude', [$device->longitude - 0.05, $device->longitude + 0.05])
                    ->where('fetched_at', '>=', now()->subHours(2))
                    ->latest('fetched_at')
                    ->first();
            }

            $recommendations = $this->generateRecommendationsForDevice($device, $latestReading, $weather);

            foreach ($recommendations as $recommendationData) {
                if ($this->shouldCreateRecommendation($device->id, $recommendationData['type'])) {
                    Recommendation::create([
                        'device_id' => $device->id,
                        'user_id' => $device->user_id,
                        ...$recommendationData,
                    ]);
                    $generated++;
                }
            }
        }

        $this->info("Generated {$generated} new recommendations for ".$devices->count().' devices.');

        return self::SUCCESS;
    }

    protected function generateRecommendationsForDevice(Device $device, SensorReading $reading, ?ExternalWeatherData $weather): array
    {
        $recommendations = [];

        if ($rec = $this->checkTemperature($reading, $weather)) {
            $recommendations[] = $rec;
        }

        if ($rec = $this->checkHumidity($reading, $weather)) {
            $recommendations[] = $rec;
        }

        if ($rec = $this->checkAirQuality($reading, $weather)) {
            $recommendations[] = $rec;
        }

        if ($rec = $this->checkLighting($reading)) {
            $recommendations[] = $rec;
        }

        if ($rec = $this->checkNoise($reading)) {
            $recommendations[] = $rec;
        }

        if ($rec = $this->checkBreak($device, $reading)) {
            $recommendations[] = $rec;
        }

        return $recommendations;
    }

    protected function checkTemperature(SensorReading $reading, ?ExternalWeatherData $weather): ?array
    {
        if (! $weather) {
            return null;
        }

        $indoorTemp = $reading->temperature;
        $outdoorTemp = $weather->outdoor_temperature;

        if ($indoorTemp > 26 && $outdoorTemp < $indoorTemp - 3) {
            return [
                'type' => 'temperature',
                'title' => 'Cool Down Your Space',
                'message' => "It's {$indoorTemp}°C inside but only {$outdoorTemp}°C outside. Open windows to cool down naturally.",
                'priority' => $indoorTemp > 30 ? 'high' : 'medium',
                'metadata' => json_encode([
                    'indoor_temp' => $indoorTemp,
                    'outdoor_temp' => $outdoorTemp,
                    'difference' => round($indoorTemp - $outdoorTemp, 1),
                ]),
            ];
        }

        if ($indoorTemp < 18 && $outdoorTemp > $indoorTemp + 3) {
            return [
                'type' => 'temperature',
                'title' => 'Warm Up Your Space',
                'message' => "It's {$indoorTemp}°C inside but {$outdoorTemp}°C outside. Opening windows could help warm your space naturally.",
                'priority' => $indoorTemp < 15 ? 'high' : 'medium',
                'metadata' => json_encode([
                    'indoor_temp' => $indoorTemp,
                    'outdoor_temp' => $outdoorTemp,
                    'difference' => round($outdoorTemp - $indoorTemp, 1),
                ]),
            ];
        }

        return null;
    }

    protected function checkHumidity(SensorReading $reading, ?ExternalWeatherData $weather): ?array
    {
        $humidity = $reading->humidity;
        $outdoorHumidity = $weather?->outdoor_humidity;

        if ($humidity > 60) {
            $canVentilate = $outdoorHumidity && $outdoorHumidity < $humidity - 10;

            if ($canVentilate) {
                return [
                    'type' => 'humidity',
                    'title' => 'High Humidity Detected',
                    'message' => "Indoor humidity is {$humidity}%, outdoor is {$outdoorHumidity}%. Open windows to reduce humidity naturally.",
                    'priority' => $humidity > 70 ? 'high' : 'medium',
                    'metadata' => json_encode([
                        'indoor_humidity' => $humidity,
                        'outdoor_humidity' => $outdoorHumidity,
                        'can_ventilate' => true,
                    ]),
                ];
            } else {
                $outdoorNote = $outdoorHumidity ? " (outdoor humidity is also high at {$outdoorHumidity}%)" : '';

                return [
                    'type' => 'humidity',
                    'title' => 'High Humidity Detected',
                    'message' => "Humidity is at {$humidity}%{$outdoorNote}. Use a dehumidifier to reduce indoor humidity.",
                    'priority' => $humidity > 70 ? 'high' : 'medium',
                    'metadata' => json_encode([
                        'indoor_humidity' => $humidity,
                        'outdoor_humidity' => $outdoorHumidity,
                        'can_ventilate' => false,
                    ]),
                ];
            }
        }

        if ($humidity < 30) {
            $canVentilate = $outdoorHumidity && $outdoorHumidity > $humidity + 10;

            if ($canVentilate) {
                return [
                    'type' => 'humidity',
                    'title' => 'Low Humidity Detected',
                    'message' => "Indoor humidity is {$humidity}%, outdoor is {$outdoorHumidity}%. Open windows to increase humidity naturally.",
                    'priority' => 'medium',
                    'metadata' => json_encode([
                        'indoor_humidity' => $humidity,
                        'outdoor_humidity' => $outdoorHumidity,
                        'can_ventilate' => true,
                    ]),
                ];
            } else {
                $outdoorNote = $outdoorHumidity ? " (outdoor humidity is also low at {$outdoorHumidity}%)" : '';

                return [
                    'type' => 'humidity',
                    'title' => 'Low Humidity Detected',
                    'message' => "Humidity is at {$humidity}%{$outdoorNote}. Use a humidifier or place water containers in the room.",
                    'priority' => 'medium',
                    'metadata' => json_encode([
                        'indoor_humidity' => $humidity,
                        'outdoor_humidity' => $outdoorHumidity,
                        'can_ventilate' => false,
                    ]),
                ];
            }
        }

        return null;
    }

    protected function checkAirQuality(SensorReading $reading, ?ExternalWeatherData $weather): ?array
    {
        if (! $reading->tvoc_ppm || $reading->tvoc_ppm <= 1000) {
            return null;
        }

        $tvoc = $reading->tvoc_ppm;
        $priority = $tvoc > 3000 ? 'high' : 'medium';

        $outdoorAqi = $weather?->outdoor_aqi;
        $outdoorPm25 = $weather?->outdoor_pm25;

        $isOutdoorAirGood = $outdoorAqi && $outdoorAqi <= 2;
        $isOutdoorPm25Good = $outdoorPm25 === null || $outdoorPm25 < 35;
        $canVentilate = $weather && $isOutdoorAirGood && $isOutdoorPm25Good;

        if ($canVentilate) {
            $aqiNote = $outdoorAqi ? " Outdoor AQI is {$outdoorAqi} (good)" : '';

            return [
                'type' => 'ventilate',
                'title' => 'Poor Air Quality Detected',
                'message' => "Indoor TVOC level is {$tvoc} ppm.{$aqiNote}. Open windows to ventilate your space.",
                'priority' => $priority,
                'metadata' => json_encode([
                    'tvoc_ppm' => $tvoc,
                    'outdoor_aqi' => $outdoorAqi,
                    'outdoor_pm25' => $outdoorPm25,
                    'can_ventilate' => true,
                ]),
            ];
        } else {
            $aqiReason = '';
            if ($outdoorAqi && $outdoorAqi > 2) {
                $aqiReason = " (outdoor air quality is also poor with AQI {$outdoorAqi})";
            } elseif ($outdoorPm25 && $outdoorPm25 >= 35) {
                $aqiReason = " (outdoor PM2.5 is high at {$outdoorPm25} μg/m³)";
            }

            return [
                'type' => 'ventilate',
                'title' => 'Poor Air Quality Detected',
                'message' => "TVOC level is {$tvoc} ppm{$aqiReason}. Use an air purifier with HEPA filter to improve indoor air quality.",
                'priority' => $priority,
                'metadata' => json_encode([
                    'tvoc_ppm' => $tvoc,
                    'outdoor_aqi' => $outdoorAqi,
                    'outdoor_pm25' => $outdoorPm25,
                    'can_ventilate' => false,
                ]),
            ];
        }
    }

    protected function checkLighting(SensorReading $reading): ?array
    {
        $light = $reading->light;

        if ($light < 300) {
            return [
                'type' => 'lighting',
                'title' => 'Insufficient Lighting',
                'message' => "Current light level is {$light} lux. Add more lighting to reduce eye strain. Recommended: 300-500 lux for office work.",
                'priority' => 'low',
                'metadata' => json_encode(['light_lux' => $light]),
            ];
        }

        if ($light > 1000) {
            return [
                'type' => 'lighting',
                'title' => 'Excessive Lighting',
                'message' => "Current light level is {$light} lux. Consider reducing lighting to avoid eye strain. Recommended: 300-500 lux for office work.",
                'priority' => 'low',
                'metadata' => json_encode(['light_lux' => $light]),
            ];
        }

        return null;
    }

    protected function checkNoise(SensorReading $reading): ?array
    {
        $noise = $reading->noise;

        if ($noise <= 70) {
            return null;
        }

        return [
            'type' => 'noise',
            'title' => 'High Noise Level',
            'message' => "Noise level is {$noise} dB. Consider taking a break in a quieter area or using noise-cancelling headphones.",
            'priority' => $noise > 85 ? 'high' : 'medium',
            'metadata' => json_encode(['noise_db' => $noise]),
        ];
    }

    protected function checkBreak(Device $device, SensorReading $reading): ?array
    {
        $recentReadingsCount = SensorReading::query()
            ->where('device_id', $device->id)
            ->where('reading_timestamp', '>=', now()->subHours(2))
            ->count();

        if ($recentReadingsCount < 24) {
            return null;
        }

        $hasSuboptimalConditions = $reading->temperature > 26
            || $reading->humidity > 60
            || ($reading->tvoc_ppm && $reading->tvoc_ppm > 1000);

        if (! $hasSuboptimalConditions) {
            return null;
        }

        return [
            'type' => 'break',
            'title' => 'Time for a Break',
            'message' => "You've been in this environment for over 2 hours with suboptimal conditions. Take a 15-minute break in a better environment.",
            'priority' => 'medium',
            'metadata' => json_encode(['hours_active' => 2]),
        ];
    }

    protected function shouldCreateRecommendation(int $deviceId, string $type): bool
    {
        return ! Recommendation::query()
            ->where('device_id', $deviceId)
            ->where('type', $type)
            ->where('status', 'pending')
            ->where('created_at', '>=', now()->subHours(2))
            ->exists();
    }
}
