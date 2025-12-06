<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Device;
use App\Models\Recommendation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class RecommendationControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    public function test_authenticated_user_can_list_their_recommendations(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->for($user)->create();
        Recommendation::factory(3)->for($device)->for($user)->create();

        $response = $this->actingAsUser($user)
            ->getJson(route('recommendations.index'));

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'device_id', 'type', 'title', 'message', 'priority', 'status'],
                ],
                'links',
                'meta',
            ])
            ->assertJsonCount(3, 'data');
    }

    public function test_recommendations_sorted_by_priority_then_date(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->for($user)->create();

        $lowRecommendation = Recommendation::factory()->for($device)->for($user)->create([
            'priority' => 'low',
            'status' => 'pending',
            'created_at' => now()->subHours(1),
        ]);
        $highRecommendation = Recommendation::factory()->for($device)->for($user)->create([
            'priority' => 'high',
            'status' => 'pending',
            'created_at' => now()->subHours(2),
        ]);
        $mediumRecommendation = Recommendation::factory()->for($device)->for($user)->create([
            'priority' => 'medium',
            'status' => 'pending',
            'created_at' => now()->subHours(3),
        ]);

        $response = $this->actingAsUser($user)
            ->getJson(route('recommendations.pending'));

        $response->assertOk();

        $data = $response->json('data');
        $this->assertEquals($highRecommendation->id, $data[0]['id']);
        $this->assertEquals($mediumRecommendation->id, $data[1]['id']);
        $this->assertEquals($lowRecommendation->id, $data[2]['id']);
    }

    public function test_recommendation_list_excludes_other_users(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $userDevice = Device::factory()->for($user)->create();
        $otherDevice = Device::factory()->for($otherUser)->create();

        Recommendation::factory(2)->for($userDevice)->for($user)->create();
        $otherRecommendation = Recommendation::factory()->for($otherDevice)->for($otherUser)->create();

        $response = $this->actingAsUser($user)
            ->getJson(route('recommendations.index'));

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonMissing(['id' => $otherRecommendation->id]);
    }

    public function test_authenticated_user_can_view_recommendation(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->for($user)->create();
        $recommendation = Recommendation::factory()->for($device)->for($user)->create();

        $response = $this->actingAsUser($user)
            ->getJson(route('recommendations.show', $recommendation));

        $response->assertOk()
            ->assertJsonStructure([
                'data' => ['id', 'device_id', 'type', 'title', 'message', 'priority', 'status'],
            ])
            ->assertJson([
                'data' => [
                    'id' => $recommendation->id,
                ],
            ]);
    }

    public function test_user_cannot_view_another_users_recommendation(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $ownerDevice = Device::factory()->for($owner)->create();
        $recommendation = Recommendation::factory()->for($ownerDevice)->for($owner)->create();

        $response = $this->actingAsUser($intruder)
            ->getJson(route('recommendations.show', $recommendation));

        $response->assertForbidden();
    }

    public function test_authenticated_user_can_get_pending_recommendations(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->for($user)->create();

        Recommendation::factory()->for($device)->for($user)->create(['status' => 'pending']);
        Recommendation::factory()->for($device)->for($user)->create(['status' => 'pending']);
        Recommendation::factory()->for($device)->for($user)->create(['status' => 'acknowledged']);
        Recommendation::factory()->for($device)->for($user)->create(['status' => 'dismissed']);

        $response = $this->actingAsUser($user)
            ->getJson('/api/recommendations/pending');

        $response->assertOk()
            ->assertJsonCount(2, 'data');

        foreach ($response->json('data') as $recommendation) {
            $this->assertEquals('pending', $recommendation['status']);
        }
    }

    public function test_pending_recommendations_sorted_by_priority(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->for($user)->create();

        $lowPending = Recommendation::factory()->for($device)->for($user)->create([
            'status' => 'pending',
            'priority' => 'low',
        ]);
        $highPending = Recommendation::factory()->for($device)->for($user)->create([
            'status' => 'pending',
            'priority' => 'high',
        ]);
        $mediumPending = Recommendation::factory()->for($device)->for($user)->create([
            'status' => 'pending',
            'priority' => 'medium',
        ]);

        $response = $this->actingAsUser($user)
            ->getJson('/api/recommendations/pending');

        $response->assertOk();

        $data = $response->json('data');
        $this->assertEquals($highPending->id, $data[0]['id']);
        $this->assertEquals($mediumPending->id, $data[1]['id']);
        $this->assertEquals($lowPending->id, $data[2]['id']);
    }

    public function test_authenticated_user_can_acknowledge_recommendation(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->for($user)->create();
        $recommendation = Recommendation::factory()->for($device)->for($user)->create([
            'status' => 'pending',
            'acknowledged_at' => null,
        ]);

        $response = $this->actingAsUser($user)
            ->patchJson('/api/recommendations/acknowledge', [
                'id' => $recommendation->id,
            ]);

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'id' => $recommendation->id,
                    'status' => 'acknowledged',
                ],
            ]);

        $this->assertDatabaseHas('recommendations', [
            'id' => $recommendation->id,
            'status' => 'acknowledged',
        ]);

        $this->assertNotNull($recommendation->fresh()->acknowledged_at);
    }

    public function test_acknowledge_validates_recommendation_id(): void
    {
        $response = $this->actingAsUser()
            ->patchJson('/api/recommendations/acknowledge', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['id']);
    }

    public function test_acknowledge_validates_recommendation_exists(): void
    {
        $response = $this->actingAsUser()
            ->patchJson('/api/recommendations/acknowledge', [
                'id' => 99999,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['id']);
    }

    public function test_user_cannot_acknowledge_another_users_recommendation(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $ownerDevice = Device::factory()->for($owner)->create();
        $recommendation = Recommendation::factory()->for($ownerDevice)->for($owner)->create([
            'status' => 'pending',
        ]);

        $response = $this->actingAsUser($intruder)
            ->patchJson('/api/recommendations/acknowledge', [
                'id' => $recommendation->id,
            ]);

        $response->assertForbidden();
    }

    public function test_authenticated_user_can_dismiss_recommendation(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->for($user)->create();
        $recommendation = Recommendation::factory()->for($device)->for($user)->create([
            'status' => 'pending',
            'dismissed_at' => null,
        ]);

        $response = $this->actingAsUser($user)
            ->patchJson('/api/recommendations/dismiss', [
                'id' => $recommendation->id,
            ]);

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'id' => $recommendation->id,
                    'status' => 'dismissed',
                ],
            ]);

        $this->assertDatabaseHas('recommendations', [
            'id' => $recommendation->id,
            'status' => 'dismissed',
        ]);

        $this->assertNotNull($recommendation->fresh()->dismissed_at);
    }

    public function test_user_cannot_dismiss_another_users_recommendation(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $ownerDevice = Device::factory()->for($owner)->create();
        $recommendation = Recommendation::factory()->for($ownerDevice)->for($owner)->create([
            'status' => 'pending',
        ]);

        $response = $this->actingAsUser($intruder)
            ->patchJson('/api/recommendations/dismiss', [
                'id' => $recommendation->id,
            ]);

        $response->assertForbidden();
    }

    public function test_dismissed_recommendation_not_in_pending_list(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->for($user)->create();
        $recommendation = Recommendation::factory()->for($device)->for($user)->create([
            'status' => 'pending',
        ]);

        $this->actingAsUser($user)
            ->patchJson('/api/recommendations/dismiss', [
                'id' => $recommendation->id,
            ]);

        $response = $this->actingAsUser($user)
            ->getJson('/api/recommendations/pending');

        $response->assertOk()
            ->assertJsonCount(0, 'data')
            ->assertJsonMissing(['id' => $recommendation->id]);
    }
}
