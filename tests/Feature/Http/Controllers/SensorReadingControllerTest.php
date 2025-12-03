<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Device;
use App\Models\SensorReading;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Carbon;
use JMac\Testing\Traits\AdditionalAssertions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\SensorReadingController
 */
final class SensorReadingControllerTest extends TestCase
{
    use AdditionalAssertions, RefreshDatabase, WithFaker;

    #[Test]
    public function store_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\SensorReadingController::class,
            'store',
            \App\Http\Requests\SensorReadingStoreRequest::class
        );
    }

    #[Test]
    public function store_saves_and_responds_with(): void
    {
        $device = Device::factory()->create();
        $temperature = fake()->randomFloat(/** decimal_attributes **/);
        $humidity = fake()->randomFloat(/** decimal_attributes **/);
        $tvoc_ppm = fake()->randomNumber();
        $light = fake()->randomNumber();
        $noise = fake()->randomNumber();

        $response = $this->post(route('sensor-readings.store'), [
            'device_id' => $device->id,
            'temperature' => $temperature,
            'humidity' => $humidity,
            'tvoc_ppm' => $tvoc_ppm,
            'light' => $light,
            'noise' => $noise,
        ]);

        $sensorReadings = SensorReading::query()
            ->where('device_id', $device->id)
            ->where('temperature', $temperature)
            ->where('humidity', $humidity)
            ->where('tvoc_ppm', $tvoc_ppm)
            ->where('light', $light)
            ->where('noise', $noise)
            ->get();
        $this->assertCount(1, $sensorReadings);
        $sensorReading = $sensorReadings->first();

        $response->assertNoContent(201);
    }

    #[Test]
    public function current_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\SensorReadingController::class,
            'current',
            \App\Http\Requests\SensorReadingCurrentRequest::class
        );
    }

    #[Test]
    public function current_behaves_as_expected(): void
    {
        $device = Device::factory()->create();
        $sensorReadings = SensorReading::factory()->count(3)->create();

        $response = $this->get(route('sensor-readings.current'), [
            'device_id' => $device->id,
        ]);

        $response->assertOk();
        $response->assertJsonStructure([]);
    }

    #[Test]
    public function history_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\SensorReadingController::class,
            'history',
            \App\Http\Requests\SensorReadingHistoryRequest::class
        );
    }

    #[Test]
    public function history_behaves_as_expected(): void
    {
        $device = Device::factory()->create();
        $temperature = fake()->randomFloat(/** decimal_attributes **/);
        $humidity = fake()->randomFloat(/** decimal_attributes **/);
        $light = fake()->randomNumber();
        $noise = fake()->randomNumber();
        $reading_timestamp = Carbon::parse(fake()->dateTime());
        $sensorReadings = SensorReading::factory()->count(3)->create();

        $response = $this->get(route('sensor-readings.history'), [
            'device_id' => $device->id,
            'temperature' => $temperature,
            'humidity' => $humidity,
            'light' => $light,
            'noise' => $noise,
            'reading_timestamp' => $reading_timestamp->toDateTimeString(),
        ]);

        $response->assertOk();
        $response->assertJsonStructure([]);
    }
}
