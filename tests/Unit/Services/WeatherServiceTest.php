<?php

namespace Tests\Unit\Services;

use App\Services\WeatherService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class WeatherServiceTest extends TestCase
{
    protected WeatherService $weatherService;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('services.weatherapi.api_key', 'test-api-key');
        Config::set('services.weatherapi.base_url', 'https://api.weatherapi.com/v1');

        $this->weatherService = new WeatherService;
    }

    public function test_can_fetch_weather_for_location(): void
    {
        Http::fake([
            'api.weatherapi.com/*' => Http::response([
                'location' => ['name' => 'Kyiv'],
                'current' => [
                    'temp_c' => 22.5,
                    'humidity' => 65,
                    'condition' => ['text' => 'Clear'],
                    'air_quality' => [
                        'us-epa-index' => 1,
                        'pm2_5' => 10.0,
                        'pm10' => 15.0,
                    ],
                ],
            ], 200),
        ]);

        $result = $this->weatherService->fetchWeatherForLocation(50.4501, 30.5234);

        $this->assertIsArray($result);
        $this->assertEquals('Kyiv', $result['location']);
        $this->assertEquals(22.5, $result['outdoor_temperature']);
        $this->assertEquals(65, $result['outdoor_humidity']);
        $this->assertEquals('weatherapi', $result['source']);
    }

    public function test_parses_weather_api_response_correctly(): void
    {
        Http::fake([
            'api.weatherapi.com/*' => Http::response([
                'location' => ['name' => 'Kyiv'],
                'current' => [
                    'temp_c' => 22.5,
                    'humidity' => 65,
                    'condition' => ['text' => 'Partly cloudy'],
                    'air_quality' => [
                        'us-epa-index' => 2,
                        'pm2_5' => 25.5,
                        'pm10' => 35.0,
                    ],
                ],
            ], 200),
        ]);

        $result = $this->weatherService->fetchWeatherForLocation(50.4501, 30.5234);

        $this->assertEquals(50.4501, $result['latitude']);
        $this->assertEquals(30.5234, $result['longitude']);
        $this->assertEquals(2, $result['outdoor_aqi']);
        $this->assertEquals(25.5, $result['outdoor_pm25']);
        $this->assertEquals(35.0, $result['outdoor_pm10']);
        $this->assertEquals('Partly cloudy', $result['weather_condition']);
        $this->assertArrayHasKey('fetched_at', $result);
    }

    public function test_handles_missing_optional_fields(): void
    {
        Http::fake([
            'api.weatherapi.com/*' => Http::response([
                'location' => ['name' => 'Kyiv'],
                'current' => [
                    'temp_c' => 22.5,
                    'humidity' => 65,
                ],
            ], 200),
        ]);

        $result = $this->weatherService->fetchWeatherForLocation(50.4501, 30.5234);

        $this->assertIsArray($result);
        $this->assertNull($result['outdoor_aqi']);
        $this->assertNull($result['outdoor_pm25']);
        $this->assertNull($result['outdoor_pm10']);
        $this->assertNull($result['weather_condition']);
    }

    public function test_returns_null_when_api_key_not_configured(): void
    {
        Config::set('services.weatherapi.api_key', null);
        Log::shouldReceive('warning')
            ->once()
            ->with('WeatherAPI key not configured');

        $weatherService = new WeatherService;
        $result = $weatherService->fetchWeatherForLocation(50.4501, 30.5234);

        $this->assertNull($result);
    }

    public function test_returns_null_when_api_request_fails(): void
    {
        Http::fake([
            'api.weatherapi.com/*' => Http::response(['error' => 'Not found'], 404),
        ]);

        Log::shouldReceive('error')
            ->once()
            ->withArgs(function ($message, $context) {
                return $message === 'WeatherAPI request failed' &&
                       $context['status'] === 404 &&
                       isset($context['latitude']) &&
                       isset($context['longitude']);
            });

        $result = $this->weatherService->fetchWeatherForLocation(50.4501, 30.5234);

        $this->assertNull($result);
    }

    public function test_returns_null_on_http_exception(): void
    {
        Http::fake([
            'api.weatherapi.com/*' => function () {
                throw new \Exception('Connection error');
            },
        ]);

        Log::shouldReceive('error')
            ->once()
            ->withArgs(function ($message, $context) {
                return $message === 'WeatherAPI exception' &&
                       $context['message'] === 'Connection error' &&
                       isset($context['latitude']) &&
                       isset($context['longitude']);
            });

        $result = $this->weatherService->fetchWeatherForLocation(50.4501, 30.5234);

        $this->assertNull($result);
    }

    public function test_handles_timeout_gracefully(): void
    {
        Http::fake([
            'api.weatherapi.com/*' => function () {
                throw new \Illuminate\Http\Client\ConnectionException('Timeout');
            },
        ]);

        Log::shouldReceive('error')
            ->once()
            ->withArgs(function ($message, $context) {
                return $message === 'WeatherAPI exception' &&
                       $context['message'] === 'Timeout';
            });

        $result = $this->weatherService->fetchWeatherForLocation(50.4501, 30.5234);

        $this->assertNull($result);
    }

    public function test_caches_weather_data_for_one_hour(): void
    {
        Http::fake([
            'api.weatherapi.com/*' => Http::response([
                'location' => ['name' => 'Kyiv'],
                'current' => [
                    'temp_c' => 22.5,
                    'humidity' => 65,
                    'condition' => ['text' => 'Clear'],
                    'air_quality' => [
                        'us-epa-index' => 1,
                        'pm2_5' => 10.0,
                        'pm10' => 15.0,
                    ],
                ],
            ], 200),
        ]);

        $result1 = $this->weatherService->getOrFetchWeather(50.4501, 30.5234);
        $result2 = $this->weatherService->getOrFetchWeather(50.4501, 30.5234);

        Http::assertSentCount(1);

        $this->assertEquals($result1, $result2);
    }

    public function test_cache_key_rounds_coordinates(): void
    {
        Http::fake([
            'api.weatherapi.com/*' => Http::response([
                'location' => ['name' => 'Kyiv'],
                'current' => [
                    'temp_c' => 22.5,
                    'humidity' => 65,
                    'condition' => ['text' => 'Clear'],
                    'air_quality' => [
                        'us-epa-index' => 1,
                        'pm2_5' => 10.0,
                        'pm10' => 15.0,
                    ],
                ],
            ], 200),
        ]);

        $result1 = $this->weatherService->getOrFetchWeather(50.456789, 30.523456);
        $result2 = $this->weatherService->getOrFetchWeather(50.451234, 30.529876);

        Http::assertSentCount(1);

        $cacheKey = 'weather:50.46:30.52';
        $this->assertTrue(Cache::has($cacheKey));
    }

    public function test_different_coordinates_use_different_cache_keys(): void
    {
        Http::fake([
            'api.weatherapi.com/*' => Http::response([
                'location' => ['name' => 'Test'],
                'current' => [
                    'temp_c' => 22.5,
                    'humidity' => 65,
                    'condition' => ['text' => 'Clear'],
                    'air_quality' => [
                        'us-epa-index' => 1,
                        'pm2_5' => 10.0,
                        'pm10' => 15.0,
                    ],
                ],
            ], 200),
        ]);

        $this->weatherService->getOrFetchWeather(50.4501, 30.5234);
        $this->weatherService->getOrFetchWeather(48.8566, 2.3522);

        Http::assertSentCount(2);

        $this->assertTrue(Cache::has('weather:50.45:30.52'));
        $this->assertTrue(Cache::has('weather:48.86:2.35'));
    }
}
