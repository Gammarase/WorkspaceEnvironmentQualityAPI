<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Device;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class DeviceControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    public function test_authenticated_user_can_list_their_devices(): void
    {
        $user = User::factory()->create();
        $devices = Device::factory(3)->for($user)->create();

        $response = $this->actingAsUser($user)
            ->getJson(route('devices.index'));

        $response->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'device_id', 'name', 'is_active'],
                ],
            ]);
    }

    public function test_device_list_excludes_other_users_devices(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        Device::factory(2)->for($user)->create();
        $otherDevice = Device::factory()->for($otherUser)->create();

        $response = $this->actingAsUser($user)
            ->getJson(route('devices.index'));

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonMissing(['device_id' => $otherDevice->device_id]);
    }

    public function test_device_list_requires_authentication(): void
    {
        $response = $this->getJson(route('devices.index'));

        $response->assertUnauthorized();
    }

    public function test_authenticated_user_can_create_device(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAsUser($user)
            ->postJson(route('devices.store'), [
                'device_id' => 'TEST-DEVICE-001',
                'name' => 'Test Device',
                'latitude' => 50.4501,
                'longitude' => 30.5234,
                'description' => 'Test description',
            ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'id', 'device_id', 'name', 'latitude', 'longitude',
            ])
            ->assertJson([
                'device_id' => 'TEST-DEVICE-001',
                'name' => 'Test Device',
            ]);

        $this->assertDatabaseHas('devices', [
            'device_id' => 'TEST-DEVICE-001',
            'user_id' => $user->id,
        ]);
    }

    public function test_device_creation_validates_required_fields(): void
    {
        $response = $this->actingAsUser()
            ->postJson(route('devices.store'), []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['device_id', 'name']);
    }

    public function test_device_id_must_be_unique(): void
    {
        Device::factory()->create(['device_id' => 'EXISTING-DEVICE']);

        $response = $this->actingAsUser()
            ->postJson(route('devices.store'), [
                'device_id' => 'EXISTING-DEVICE',
                'name' => 'Test Device',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['device_id']);
    }

    public function test_device_creation_validates_coordinates(): void
    {
        $response = $this->actingAsUser()
            ->postJson(route('devices.store'), [
                'device_id' => 'TEST-001',
                'name' => 'Test Device',
                'latitude' => 91,
                'longitude' => 181,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['latitude', 'longitude']);
    }

    public function test_authenticated_user_can_view_their_device(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->for($user)->create();

        $response = $this->actingAsUser($user)
            ->getJson(route('devices.show', $device));

        $response->assertOk()
            ->assertJsonStructure([
                'data' => ['id', 'device_id', 'name', 'is_active'],
            ])
            ->assertJson([
                'data' => [
                    'id' => $device->id,
                    'device_id' => $device->device_id,
                ],
            ]);
    }

    public function test_user_cannot_view_another_users_device(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $device = Device::factory()->for($owner)->create();

        $response = $this->actingAsUser($intruder)
            ->getJson(route('devices.show', $device));

        $response->assertForbidden();
    }

    public function test_authenticated_user_can_update_their_device(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->for($user)->create(['name' => 'Old Name']);

        $response = $this->actingAsUser($user)
            ->patchJson(route('devices.update', $device), [
                'name' => 'Updated Name',
                'description' => 'Updated description',
            ]);

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'name' => 'Updated Name',
                ],
            ]);

        $this->assertDatabaseHas('devices', [
            'id' => $device->id,
            'name' => 'Updated Name',
            'description' => 'Updated description',
        ]);
    }

    public function test_user_cannot_update_another_users_device(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $device = Device::factory()->for($owner)->create();

        $response = $this->actingAsUser($intruder)
            ->patchJson(route('devices.update', $device), [
                'name' => 'Hacked Name',
            ]);

        $response->assertForbidden();
    }

    public function test_device_update_validates_fields(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->for($user)->create();

        $response = $this->actingAsUser($user)
            ->patchJson(route('devices.update', $device), [
                'latitude' => 999.99,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['latitude']);
    }

    public function test_authenticated_user_can_delete_their_device(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->for($user)->create();

        $response = $this->actingAsUser($user)
            ->deleteJson(route('devices.destroy', $device));

        $response->assertNoContent();

        $this->assertSoftDeleted('devices', [
            'id' => $device->id,
        ]);
    }

    public function test_user_cannot_delete_another_users_device(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $device = Device::factory()->for($owner)->create();

        $response = $this->actingAsUser($intruder)
            ->deleteJson(route('devices.destroy', $device));

        $response->assertForbidden();
    }

    public function test_deleted_device_not_in_device_list(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->for($user)->create();

        $device->delete();

        $response = $this->actingAsUser($user)
            ->getJson(route('devices.index'));

        $response->assertOk()
            ->assertJsonCount(0, 'data')
            ->assertJsonMissing(['device_id' => $device->device_id]);
    }
}
