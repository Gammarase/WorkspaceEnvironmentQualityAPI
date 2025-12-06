<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Device;
use App\Models\SensorReading;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class SensorReadingControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    public function test_authenticated_user_can_submit_sensor_reading(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->for($user)->create();

        $response = $this->actingAsUser($user)
            ->postJson(route('sensor-readings.store'), [
                'device_id' => $device->id,
                'temperature' => 22.5,
                'humidity' => 45.0,
                'tvoc_ppm' => 500,
                'light' => 350,
                'noise' => 40,
            ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'id', 'device_id', 'temperature', 'humidity', 'tvoc_ppm', 'light', 'noise',
            ])
            ->assertJson([
                'device_id' => $device->id,
                'temperature' => 22.5,
                'humidity' => 45.0,
            ]);

        $this->assertDatabaseHas('sensor_readings', [
            'device_id' => $device->id,
            'temperature' => 22.5,
            'humidity' => 45.0,
        ]);
    }

    public function test_sensor_reading_updates_device_last_seen_at(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->for($user)->create(['last_seen_at' => null]);

        $this->assertNull($device->fresh()->last_seen_at);

        $this->actingAsUser($user)
            ->postJson(route('sensor-readings.store'), [
                'device_id' => $device->id,
                'temperature' => 22.5,
                'humidity' => 45.0,
                'light' => 350,
                'noise' => 40,
            ]);

        $this->assertNotNull($device->fresh()->last_seen_at);
        $this->assertTrue(Carbon::createFromTimestamp($device->fresh()->last_seen_at)->isToday());
    }

    public function test_user_cannot_submit_reading_for_another_users_device(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $device = Device::factory()->for($owner)->create();

        $response = $this->actingAsUser($intruder)
            ->postJson(route('sensor-readings.store'), [
                'device_id' => $device->id,
                'temperature' => 22.5,
                'humidity' => 45.0,
                'light' => 350,
                'noise' => 40,
            ]);

        $response->assertForbidden();
    }

    public function test_sensor_reading_validates_required_fields(): void
    {
        $response = $this->actingAsUser()
            ->postJson(route('sensor-readings.store'), []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['device_id', 'temperature', 'humidity', 'light', 'noise']);
    }

    public function test_sensor_reading_validates_device_exists(): void
    {
        $response = $this->actingAsUser()
            ->postJson(route('sensor-readings.store'), [
                'device_id' => 99999,
                'temperature' => 22.5,
                'humidity' => 45.0,
                'light' => 350,
                'noise' => 40,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['device_id']);
    }

    public function test_sensor_reading_validates_numeric_ranges(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->for($user)->create();

        $response = $this->actingAsUser($user)
            ->postJson(route('sensor-readings.store'), [
                'device_id' => $device->id,
                'temperature' => 1000.00,
                'humidity' => 1000.00,
                'light' => 350,
                'noise' => 40,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['temperature', 'humidity']);
    }

    public function test_sensor_reading_validates_positive_integers(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->for($user)->create();

        $response = $this->actingAsUser($user)
            ->postJson(route('sensor-readings.store'), [
                'device_id' => $device->id,
                'temperature' => 22.5,
                'humidity' => 45.0,
                'tvoc_ppm' => -100,
                'light' => -50,
                'noise' => 0,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['tvoc_ppm', 'light', 'noise']);
    }

    public function test_sensor_reading_timestamp_defaults_to_now(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->for($user)->create();

        $response = $this->actingAsUser($user)
            ->postJson(route('sensor-readings.store'), [
                'device_id' => $device->id,
                'temperature' => 22.5,
                'humidity' => 45.0,
                'light' => 350,
                'noise' => 40,
            ]);

        $response->assertCreated();

        $reading = SensorReading::latest()->first();
        $this->assertNotNull($reading->reading_timestamp);
        $this->assertTrue(Carbon::createFromTimestamp($reading->reading_timestamp)->isToday());
    }

    public function test_sensor_reading_accepts_past_timestamp(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->for($user)->create();
        $pastTimestamp = now()->subHours(2)->toDateTimeString();

        $response = $this->actingAsUser($user)
            ->postJson(route('sensor-readings.store'), [
                'device_id' => $device->id,
                'temperature' => 22.5,
                'humidity' => 45.0,
                'light' => 350,
                'noise' => 40,
                'reading_timestamp' => $pastTimestamp,
            ]);

        $response->assertCreated();

        $reading = SensorReading::latest()->first();
        $this->assertEquals($pastTimestamp, Carbon::createFromTimestamp($reading->reading_timestamp)->toDateTimeString());
    }

    public function test_sensor_reading_rejects_future_timestamp(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->for($user)->create();

        $response = $this->actingAsUser($user)
            ->postJson(route('sensor-readings.store'), [
                'device_id' => $device->id,
                'temperature' => 22.5,
                'humidity' => 45.0,
                'light' => 350,
                'noise' => 40,
                'reading_timestamp' => now()->addHours(2)->toDateTimeString(),
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['reading_timestamp']);
    }

    public function test_authenticated_user_can_get_current_reading(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->for($user)->create();
        $reading = SensorReading::factory()->for($device)->create([
            'reading_timestamp' => now(),
        ]);

        $response = $this->actingAsUser($user)
            ->getJson('/api/sensor-readings/current?device_id='.$device->id);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => ['id', 'device_id', 'temperature', 'humidity'],
            ])
            ->assertJson([
                'data' => [
                    'id' => $reading->id,
                    'device_id' => $device->id,
                ],
            ]);
    }

    public function test_current_reading_requires_device_id(): void
    {
        $response = $this->actingAsUser()
            ->getJson('/api/sensor-readings/current');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['device_id']);
    }

    public function test_user_cannot_get_current_reading_for_another_users_device(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $device = Device::factory()->for($owner)->create();
        SensorReading::factory()->for($device)->create();

        $response = $this->actingAsUser($intruder)
            ->getJson('/api/sensor-readings/current?device_id='.$device->id);

        $response->assertForbidden();
    }

    public function test_current_reading_returns_404_when_no_readings(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->for($user)->create();

        $response = $this->actingAsUser($user)
            ->getJson('/api/sensor-readings/current?device_id='.$device->id);

        $response->assertNotFound();
    }

    public function test_authenticated_user_can_get_reading_history(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->for($user)->create();
        SensorReading::factory(5)->for($device)->create([
            'reading_timestamp' => now(),
        ]);

        $response = $this->actingAsUser($user)
            ->getJson('/api/sensor-readings/history?device_id='.$device->id.'&start_date='.now()->subDays(1)->toDateString().'&end_date='.now()->toDateString());

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'device_id', 'temperature', 'humidity'],
                ],
            ]);
    }

    public function test_history_filters_by_date_range(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->for($user)->create();

        SensorReading::factory()->for($device)->create(['reading_timestamp' => now()->subDays(5)]);
        $inRangeReading = SensorReading::factory()->for($device)->create(['reading_timestamp' => now()->subDays(1)]);
        SensorReading::factory()->for($device)->create(['reading_timestamp' => now()->addDays(1)]);

        $response = $this->actingAsUser($user)
            ->getJson('/api/sensor-readings/history?device_id='.$device->id.'&start_date='.now()->subDays(2)->toDateString().'&end_date='.now()->toDateString());

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['id' => $inRangeReading->id]);
    }

    public function test_history_validates_required_parameters(): void
    {
        $response = $this->actingAsUser()
            ->getJson('/api/sensor-readings/history');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['device_id', 'start_date', 'end_date']);
    }

    public function test_history_orders_by_timestamp_descending(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->for($user)->create();

        $oldReading = SensorReading::factory()->for($device)->create(['reading_timestamp' => now()->subHours(3)]);
        $middleReading = SensorReading::factory()->for($device)->create(['reading_timestamp' => now()->subHours(2)]);
        $newReading = SensorReading::factory()->for($device)->create(['reading_timestamp' => now()->subHours(1)]);

        $response = $this->actingAsUser($user)
            ->getJson('/api/sensor-readings/history?device_id='.$device->id.'&start_date='.now()->subDays(1)->toDateString().'&end_date='.now()->toDateString());

        $response->assertOk();

        $data = $response->json('data');
        $this->assertEquals($newReading->id, $data[0]['id']);
        $this->assertEquals($middleReading->id, $data[1]['id']);
        $this->assertEquals($oldReading->id, $data[2]['id']);
    }

    public function test_user_cannot_get_history_for_another_users_device(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $device = Device::factory()->for($owner)->create();
        SensorReading::factory()->for($device)->create();

        $response = $this->actingAsUser($intruder)
            ->getJson('/api/sensor-readings/history?device_id='.$device->id.'&start_date='.now()->subDays(1)->toDateString().'&end_date='.now()->toDateString());

        $response->assertForbidden();
    }
}
