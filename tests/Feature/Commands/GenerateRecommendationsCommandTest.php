<?php

namespace Tests\Feature\Commands;

use App\Models\Device;
use App\Models\ExternalWeatherData;
use App\Models\Recommendation;
use App\Models\SensorReading;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class GenerateRecommendationsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_generates_no_recommendations_when_no_active_devices(): void
    {
        $exitCode = Artisan::call('recommendations:generate');

        $this->assertEquals(0, $exitCode);
        $output = Artisan::output();
        $this->assertStringContainsString('Generated 0 new recommendations', $output);
    }

    public function test_command_skips_devices_without_recent_readings(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->for($user)->create(['is_active' => true]);
        SensorReading::factory()->for($device)->create([
            'reading_timestamp' => now()->subHours(25),
        ]);

        $exitCode = Artisan::call('recommendations:generate');

        $this->assertEquals(0, $exitCode);
        $output = Artisan::output();
        $this->assertStringContainsString('Generated 0 new recommendations', $output);
    }

    public function test_generates_temperature_recommendation_when_indoor_too_hot(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->for($user)->create(['is_active' => true, 'latitude' => 50.45, 'longitude' => 30.52]);
        SensorReading::factory()->for($device)->create([
            'temperature' => 28.0,
            'humidity' => 50,
            'light' => 400,
            'noise' => 50,
            'reading_timestamp' => now(),
        ]);
        ExternalWeatherData::factory()->create([
            'latitude' => 50.45,
            'longitude' => 30.52,
            'outdoor_temperature' => 20.0,
            'outdoor_humidity' => 40,
            'outdoor_aqi' => 1,
            'fetched_at' => now(),
        ]);

        Artisan::call('recommendations:generate');

        $this->assertDatabaseHas('recommendations', [
            'device_id' => $device->id,
            'type' => 'temperature',
            'priority' => 'medium',
        ]);
    }

    public function test_temperature_recommendation_is_high_priority_above_30(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->for($user)->create(['is_active' => true, 'latitude' => 50.45, 'longitude' => 30.52]);
        SensorReading::factory()->for($device)->create([
            'temperature' => 31.0,
            'humidity' => 50,
            'light' => 400,
            'noise' => 50,
            'reading_timestamp' => now(),
        ]);
        ExternalWeatherData::factory()->create([
            'latitude' => 50.45,
            'longitude' => 30.52,
            'outdoor_temperature' => 22.0,
            'outdoor_humidity' => 40,
            'outdoor_aqi' => 1,
            'fetched_at' => now(),
        ]);

        Artisan::call('recommendations:generate');

        $this->assertDatabaseHas('recommendations', [
            'device_id' => $device->id,
            'type' => 'temperature',
            'priority' => 'high',
        ]);
    }

    public function test_generates_temperature_recommendation_when_indoor_too_cold(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->for($user)->create(['is_active' => true, 'latitude' => 50.45, 'longitude' => 30.52]);
        SensorReading::factory()->for($device)->create([
            'temperature' => 16.0,
            'humidity' => 50,
            'light' => 400,
            'noise' => 50,
            'reading_timestamp' => now(),
        ]);
        ExternalWeatherData::factory()->create([
            'latitude' => 50.45,
            'longitude' => 30.52,
            'outdoor_temperature' => 22.0,
            'outdoor_humidity' => 40,
            'outdoor_aqi' => 1,
            'fetched_at' => now(),
        ]);

        Artisan::call('recommendations:generate');

        $this->assertDatabaseHas('recommendations', [
            'device_id' => $device->id,
            'type' => 'temperature',
            'priority' => 'medium',
        ]);
    }

    public function test_does_not_generate_temperature_recommendation_when_no_weather_data(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->for($user)->create(['is_active' => true]);
        SensorReading::factory()->for($device)->create([
            'temperature' => 28.0,
            'humidity' => 50,
            'light' => 400,
            'noise' => 50,
            'reading_timestamp' => now(),
        ]);

        Artisan::call('recommendations:generate');

        $this->assertDatabaseMissing('recommendations', [
            'device_id' => $device->id,
            'type' => 'temperature',
        ]);
    }

    public function test_generates_high_humidity_recommendation_with_ventilation(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->for($user)->create(['is_active' => true, 'latitude' => 50.45, 'longitude' => 30.52]);
        SensorReading::factory()->for($device)->create([
            'temperature' => 22.0,
            'humidity' => 65,
            'light' => 400,
            'noise' => 50,
            'reading_timestamp' => now(),
        ]);
        ExternalWeatherData::factory()->create([
            'latitude' => 50.45,
            'longitude' => 30.52,
            'outdoor_temperature' => 22.0,
            'outdoor_humidity' => 40,
            'outdoor_aqi' => 1,
            'fetched_at' => now(),
        ]);

        Artisan::call('recommendations:generate');

        $recommendation = Recommendation::where('device_id', $device->id)->where('type', 'humidity')->first();
        $this->assertNotNull($recommendation);
        $this->assertEquals('medium', $recommendation->priority);
        $this->assertStringContainsString('Open windows', $recommendation->message);
    }

    public function test_generates_high_humidity_recommendation_without_ventilation(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->for($user)->create(['is_active' => true, 'latitude' => 50.45, 'longitude' => 30.52]);
        SensorReading::factory()->for($device)->create([
            'temperature' => 22.0,
            'humidity' => 65,
            'light' => 400,
            'noise' => 50,
            'reading_timestamp' => now(),
        ]);
        ExternalWeatherData::factory()->create([
            'latitude' => 50.45,
            'longitude' => 30.52,
            'outdoor_temperature' => 22.0,
            'outdoor_humidity' => 70,
            'outdoor_aqi' => 1,
            'fetched_at' => now(),
        ]);

        Artisan::call('recommendations:generate');

        $recommendation = Recommendation::where('device_id', $device->id)->where('type', 'humidity')->first();
        $this->assertNotNull($recommendation);
        $this->assertStringContainsString('dehumidifier', $recommendation->message);
    }

    public function test_high_humidity_priority_is_high_above_70(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->for($user)->create(['is_active' => true, 'latitude' => 50.45, 'longitude' => 30.52]);
        SensorReading::factory()->for($device)->create([
            'temperature' => 22.0,
            'humidity' => 72,
            'light' => 400,
            'noise' => 50,
            'reading_timestamp' => now(),
        ]);
        ExternalWeatherData::factory()->create([
            'latitude' => 50.45,
            'longitude' => 30.52,
            'outdoor_temperature' => 22.0,
            'outdoor_humidity' => 50,
            'outdoor_aqi' => 1,
            'fetched_at' => now(),
        ]);

        Artisan::call('recommendations:generate');

        $this->assertDatabaseHas('recommendations', [
            'device_id' => $device->id,
            'type' => 'humidity',
            'priority' => 'high',
        ]);
    }

    public function test_generates_low_humidity_recommendation(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->for($user)->create(['is_active' => true, 'latitude' => 50.45, 'longitude' => 30.52]);
        SensorReading::factory()->for($device)->create([
            'temperature' => 22.0,
            'humidity' => 25,
            'light' => 400,
            'noise' => 50,
            'reading_timestamp' => now(),
        ]);
        ExternalWeatherData::factory()->create([
            'latitude' => 50.45,
            'longitude' => 30.52,
            'outdoor_temperature' => 22.0,
            'outdoor_humidity' => 20,
            'outdoor_aqi' => 1,
            'fetched_at' => now(),
        ]);

        Artisan::call('recommendations:generate');

        $this->assertDatabaseHas('recommendations', [
            'device_id' => $device->id,
            'type' => 'humidity',
            'priority' => 'medium',
        ]);
    }

    public function test_low_humidity_with_ventilation(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->for($user)->create(['is_active' => true, 'latitude' => 50.45, 'longitude' => 30.52]);
        SensorReading::factory()->for($device)->create([
            'temperature' => 22.0,
            'humidity' => 25,
            'light' => 400,
            'noise' => 50,
            'reading_timestamp' => now(),
        ]);
        ExternalWeatherData::factory()->create([
            'latitude' => 50.45,
            'longitude' => 30.52,
            'outdoor_temperature' => 22.0,
            'outdoor_humidity' => 50,
            'outdoor_aqi' => 1,
            'fetched_at' => now(),
        ]);

        Artisan::call('recommendations:generate');

        $recommendation = Recommendation::where('device_id', $device->id)->where('type', 'humidity')->first();
        $this->assertNotNull($recommendation);
        $this->assertStringContainsString('Open windows', $recommendation->message);
    }

    public function test_generates_air_quality_recommendation_with_ventilation(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->for($user)->create(['is_active' => true, 'latitude' => 50.45, 'longitude' => 30.52]);
        SensorReading::factory()->for($device)->create([
            'temperature' => 22.0,
            'humidity' => 50,
            'tvoc_ppm' => 1500,
            'light' => 400,
            'noise' => 50,
            'reading_timestamp' => now(),
        ]);
        ExternalWeatherData::factory()->create([
            'latitude' => 50.45,
            'longitude' => 30.52,
            'outdoor_temperature' => 22.0,
            'outdoor_humidity' => 40,
            'outdoor_aqi' => 1,
            'outdoor_pm25' => 15.0,
            'fetched_at' => now(),
        ]);

        Artisan::call('recommendations:generate');

        $recommendation = Recommendation::where('device_id', $device->id)->where('type', 'ventilate')->first();
        $this->assertNotNull($recommendation);
        $this->assertStringContainsString('Open windows', $recommendation->message);
    }

    public function test_generates_air_quality_recommendation_without_ventilation_poor_aqi(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->for($user)->create(['is_active' => true, 'latitude' => 50.45, 'longitude' => 30.52]);
        SensorReading::factory()->for($device)->create([
            'temperature' => 22.0,
            'humidity' => 50,
            'tvoc_ppm' => 1500,
            'light' => 400,
            'noise' => 50,
            'reading_timestamp' => now(),
        ]);
        ExternalWeatherData::factory()->create([
            'latitude' => 50.45,
            'longitude' => 30.52,
            'outdoor_temperature' => 22.0,
            'outdoor_humidity' => 40,
            'outdoor_aqi' => 4,
            'outdoor_pm25' => 15.0,
            'fetched_at' => now(),
        ]);

        Artisan::call('recommendations:generate');

        $recommendation = Recommendation::where('device_id', $device->id)->where('type', 'ventilate')->first();
        $this->assertNotNull($recommendation);
        $this->assertStringContainsString('air purifier', $recommendation->message);
    }

    public function test_generates_air_quality_recommendation_without_ventilation_high_pm25(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->for($user)->create(['is_active' => true, 'latitude' => 50.45, 'longitude' => 30.52]);
        SensorReading::factory()->for($device)->create([
            'temperature' => 22.0,
            'humidity' => 50,
            'tvoc_ppm' => 1500,
            'light' => 400,
            'noise' => 50,
            'reading_timestamp' => now(),
        ]);
        ExternalWeatherData::factory()->create([
            'latitude' => 50.45,
            'longitude' => 30.52,
            'outdoor_temperature' => 22.0,
            'outdoor_humidity' => 40,
            'outdoor_aqi' => 1,
            'outdoor_pm25' => 40.0,
            'fetched_at' => now(),
        ]);

        Artisan::call('recommendations:generate');

        $recommendation = Recommendation::where('device_id', $device->id)->where('type', 'ventilate')->first();
        $this->assertNotNull($recommendation);
        $this->assertStringContainsString('air purifier', $recommendation->message);
    }

    public function test_air_quality_priority_is_high_above_3000(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->for($user)->create(['is_active' => true, 'latitude' => 50.45, 'longitude' => 30.52]);
        SensorReading::factory()->for($device)->create([
            'temperature' => 22.0,
            'humidity' => 50,
            'tvoc_ppm' => 3500,
            'light' => 400,
            'noise' => 50,
            'reading_timestamp' => now(),
        ]);
        ExternalWeatherData::factory()->create([
            'latitude' => 50.45,
            'longitude' => 30.52,
            'outdoor_temperature' => 22.0,
            'outdoor_humidity' => 40,
            'outdoor_aqi' => 1,
            'fetched_at' => now(),
        ]);

        Artisan::call('recommendations:generate');

        $this->assertDatabaseHas('recommendations', [
            'device_id' => $device->id,
            'type' => 'ventilate',
            'priority' => 'high',
        ]);
    }

    public function test_no_air_quality_recommendation_when_tvoc_low(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->for($user)->create(['is_active' => true, 'latitude' => 50.45, 'longitude' => 30.52]);
        SensorReading::factory()->for($device)->create([
            'temperature' => 22.0,
            'humidity' => 50,
            'tvoc_ppm' => 800,
            'light' => 400,
            'noise' => 50,
            'reading_timestamp' => now(),
        ]);
        ExternalWeatherData::factory()->create([
            'latitude' => 50.45,
            'longitude' => 30.52,
            'outdoor_temperature' => 22.0,
            'outdoor_humidity' => 40,
            'outdoor_aqi' => 1,
            'fetched_at' => now(),
        ]);

        Artisan::call('recommendations:generate');

        $this->assertDatabaseMissing('recommendations', [
            'device_id' => $device->id,
            'type' => 'ventilate',
        ]);
    }

    public function test_generates_lighting_recommendation_when_too_dark(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->for($user)->create(['is_active' => true]);
        SensorReading::factory()->for($device)->create([
            'temperature' => 22.0,
            'humidity' => 50,
            'light' => 250,
            'noise' => 50,
            'reading_timestamp' => now(),
        ]);

        Artisan::call('recommendations:generate');

        $this->assertDatabaseHas('recommendations', [
            'device_id' => $device->id,
            'type' => 'lighting',
            'priority' => 'low',
        ]);

        $recommendation = Recommendation::where('device_id', $device->id)->where('type', 'lighting')->first();
        $this->assertStringContainsString('Insufficient Lighting', $recommendation->title);
    }

    public function test_generates_lighting_recommendation_when_too_bright(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->for($user)->create(['is_active' => true]);
        SensorReading::factory()->for($device)->create([
            'temperature' => 22.0,
            'humidity' => 50,
            'light' => 1200,
            'noise' => 50,
            'reading_timestamp' => now(),
        ]);

        Artisan::call('recommendations:generate');

        $this->assertDatabaseHas('recommendations', [
            'device_id' => $device->id,
            'type' => 'lighting',
            'priority' => 'low',
        ]);

        $recommendation = Recommendation::where('device_id', $device->id)->where('type', 'lighting')->first();
        $this->assertStringContainsString('Excessive Lighting', $recommendation->title);
    }

    public function test_no_lighting_recommendation_when_optimal(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->for($user)->create(['is_active' => true]);
        SensorReading::factory()->for($device)->create([
            'temperature' => 22.0,
            'humidity' => 50,
            'light' => 400,
            'noise' => 50,
            'reading_timestamp' => now(),
        ]);

        Artisan::call('recommendations:generate');

        $this->assertDatabaseMissing('recommendations', [
            'device_id' => $device->id,
            'type' => 'lighting',
        ]);
    }

    public function test_generates_noise_recommendation_when_high(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->for($user)->create(['is_active' => true]);
        SensorReading::factory()->for($device)->create([
            'temperature' => 22.0,
            'humidity' => 50,
            'light' => 400,
            'noise' => 75,
            'reading_timestamp' => now(),
        ]);

        Artisan::call('recommendations:generate');

        $this->assertDatabaseHas('recommendations', [
            'device_id' => $device->id,
            'type' => 'noise',
            'priority' => 'medium',
        ]);
    }

    public function test_noise_recommendation_is_high_priority_above_85(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->for($user)->create(['is_active' => true]);
        SensorReading::factory()->for($device)->create([
            'temperature' => 22.0,
            'humidity' => 50,
            'light' => 400,
            'noise' => 90,
            'reading_timestamp' => now(),
        ]);

        Artisan::call('recommendations:generate');

        $this->assertDatabaseHas('recommendations', [
            'device_id' => $device->id,
            'type' => 'noise',
            'priority' => 'high',
        ]);
    }

    public function test_generates_break_recommendation_after_long_session(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->for($user)->create(['is_active' => true]);

        SensorReading::factory(25)->for($device)->create([
            'temperature' => 27.0,
            'humidity' => 50,
            'light' => 400,
            'noise' => 50,
            'reading_timestamp' => now()->subMinutes(120),
        ]);

        Artisan::call('recommendations:generate');

        $this->assertDatabaseHas('recommendations', [
            'device_id' => $device->id,
            'type' => 'break',
            'priority' => 'medium',
        ]);
    }

    public function test_no_break_recommendation_when_conditions_good(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->for($user)->create(['is_active' => true]);

        SensorReading::factory(25)->for($device)->create([
            'temperature' => 22.0,
            'humidity' => 50,
            'light' => 400,
            'noise' => 50,
            'reading_timestamp' => now()->subMinutes(120),
        ]);

        Artisan::call('recommendations:generate');

        $this->assertDatabaseMissing('recommendations', [
            'device_id' => $device->id,
            'type' => 'break',
        ]);
    }

    public function test_no_break_recommendation_when_few_readings(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->for($user)->create(['is_active' => true]);

        SensorReading::factory(20)->for($device)->create([
            'temperature' => 27.0,
            'humidity' => 50,
            'light' => 400,
            'noise' => 50,
            'reading_timestamp' => now()->subMinutes(120),
        ]);

        Artisan::call('recommendations:generate');

        $this->assertDatabaseMissing('recommendations', [
            'device_id' => $device->id,
            'type' => 'break',
        ]);
    }

    public function test_does_not_duplicate_pending_recommendations(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->for($user)->create(['is_active' => true]);
        SensorReading::factory()->for($device)->create([
            'temperature' => 22.0,
            'humidity' => 50,
            'light' => 250,
            'noise' => 50,
            'reading_timestamp' => now(),
        ]);

        Recommendation::factory()->for($device)->for($user)->create([
            'type' => 'lighting',
            'status' => 'pending',
            'created_at' => now()->subMinutes(30),
        ]);

        Artisan::call('recommendations:generate');

        $lightingRecommendations = Recommendation::where('device_id', $device->id)
            ->where('type', 'lighting')
            ->count();

        $this->assertEquals(1, $lightingRecommendations);
    }

    public function test_creates_new_recommendation_when_previous_acknowledged(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->for($user)->create(['is_active' => true]);
        SensorReading::factory()->for($device)->create([
            'temperature' => 22.0,
            'humidity' => 50,
            'light' => 250,
            'noise' => 50,
            'reading_timestamp' => now(),
        ]);

        Recommendation::factory()->for($device)->for($user)->create([
            'type' => 'lighting',
            'status' => 'acknowledged',
            'created_at' => now()->subMinutes(30),
        ]);

        Artisan::call('recommendations:generate');

        $lightingRecommendations = Recommendation::where('device_id', $device->id)
            ->where('type', 'lighting')
            ->count();

        $this->assertEquals(2, $lightingRecommendations);
    }

    public function test_creates_new_recommendation_when_previous_older_than_2_hours(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->for($user)->create(['is_active' => true]);
        SensorReading::factory()->for($device)->create([
            'temperature' => 22.0,
            'humidity' => 50,
            'light' => 250,
            'noise' => 50,
            'reading_timestamp' => now(),
        ]);

        Recommendation::factory()->for($device)->for($user)->create([
            'type' => 'lighting',
            'status' => 'pending',
            'created_at' => now()->subHours(3),
        ]);

        Artisan::call('recommendations:generate');

        $lightingRecommendations = Recommendation::where('device_id', $device->id)
            ->where('type', 'lighting')
            ->count();

        $this->assertEquals(2, $lightingRecommendations);
    }

    public function test_command_output_summary(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->for($user)->create(['is_active' => true]);
        SensorReading::factory()->for($device)->create([
            'temperature' => 22.0,
            'humidity' => 50,
            'light' => 250,
            'noise' => 75,
            'reading_timestamp' => now(),
        ]);

        Artisan::call('recommendations:generate');

        $output = Artisan::output();
        $this->assertStringContainsString('Generated', $output);
        $this->assertStringContainsString('new recommendations', $output);
    }
}
