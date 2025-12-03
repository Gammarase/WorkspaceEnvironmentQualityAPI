<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Device;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use JMac\Testing\Traits\AdditionalAssertions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\DeviceController
 */
final class DeviceControllerTest extends TestCase
{
    use AdditionalAssertions, RefreshDatabase, WithFaker;

    #[Test]
    public function index_behaves_as_expected(): void
    {
        $devices = Device::factory()->count(3)->create();

        $response = $this->get(route('devices.index'));

        $response->assertOk();
        $response->assertJsonStructure([]);
    }

    #[Test]
    public function store_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\DeviceController::class,
            'store',
            \App\Http\Requests\DeviceStoreRequest::class
        );
    }

    #[Test]
    public function store_saves(): void
    {
        $device = Device::factory()->create();
        $name = fake()->name();
        $longitude = fake()->longitude();
        $latitude = fake()->latitude();
        $description = fake()->text();

        $response = $this->post(route('devices.store'), [
            'device_id' => $device->id,
            'name' => $name,
            'longitude' => $longitude,
            'latitude' => $latitude,
            'description' => $description,
        ]);

        $devices = Device::query()
            ->where('device_id', $device->id)
            ->where('name', $name)
            ->where('longitude', $longitude)
            ->where('latitude', $latitude)
            ->where('description', $description)
            ->get();
        $this->assertCount(1, $devices);
        $device = $devices->first();

        $response->assertCreated();
        $response->assertJsonStructure([]);
    }

    #[Test]
    public function show_behaves_as_expected(): void
    {
        $device = Device::factory()->create();

        $response = $this->get(route('devices.show', $device));

        $response->assertOk();
        $response->assertJsonStructure([]);
    }

    #[Test]
    public function update_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\DeviceController::class,
            'update',
            \App\Http\Requests\DeviceUpdateRequest::class
        );
    }

    #[Test]
    public function update_behaves_as_expected(): void
    {
        $device = Device::factory()->create();
        $name = fake()->name();
        $longitude = fake()->longitude();
        $latitude = fake()->latitude();
        $description = fake()->text();
        $is_active = fake()->boolean();

        $response = $this->put(route('devices.update', $device), [
            'name' => $name,
            'longitude' => $longitude,
            'latitude' => $latitude,
            'description' => $description,
            'is_active' => $is_active,
        ]);

        $device->refresh();

        $response->assertOk();
        $response->assertJsonStructure([]);

        $this->assertEquals($name, $device->name);
        $this->assertEquals($longitude, $device->longitude);
        $this->assertEquals($latitude, $device->latitude);
        $this->assertEquals($description, $device->description);
        $this->assertEquals($is_active, $device->is_active);
    }

    #[Test]
    public function destroy_deletes_and_responds_with(): void
    {
        $device = Device::factory()->create();

        $response = $this->delete(route('devices.destroy', $device));

        $response->assertNoContent();

        $this->assertSoftDeleted($device);
    }
}
