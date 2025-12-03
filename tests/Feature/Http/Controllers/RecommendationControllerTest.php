<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Id;
use App\Models\Recommendation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use JMac\Testing\Traits\AdditionalAssertions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\RecommendationController
 */
final class RecommendationControllerTest extends TestCase
{
    use AdditionalAssertions, RefreshDatabase, WithFaker;

    #[Test]
    public function index_behaves_as_expected(): void
    {
        $recommendations = Recommendation::factory()->count(3)->create();

        $response = $this->get(route('recommendations.index'));

        $response->assertOk();
        $response->assertJsonStructure([]);
    }

    #[Test]
    public function show_behaves_as_expected(): void
    {
        $recommendation = Recommendation::factory()->create();

        $response = $this->get(route('recommendations.show', $recommendation));

        $response->assertOk();
        $response->assertJsonStructure([]);
    }

    #[Test]
    public function acknowledge_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\RecommendationController::class,
            'acknowledge',
            \App\Http\Requests\RecommendationAcknowledgeRequest::class
        );
    }

    #[Test]
    public function acknowledge_responds_with(): void
    {
        $id = Id::factory()->create();

        $response = $this->get(route('recommendations.acknowledge'), [
            'id' => $id->id,
        ]);

        $recommendation->refresh();

        $response->assertOk();
        $response->assertJson($Recommendation);

        $this->assertEquals($id->id, $recommendation->id);
    }

    #[Test]
    public function dismiss_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\RecommendationController::class,
            'dismiss',
            \App\Http\Requests\RecommendationDismissRequest::class
        );
    }

    #[Test]
    public function dismiss_responds_with(): void
    {
        $id = Id::factory()->create();

        $response = $this->get(route('recommendations.dismiss'), [
            'id' => $id->id,
        ]);

        $recommendation->refresh();

        $response->assertOk();
        $response->assertJson($Recommendation);

        $this->assertEquals($id->id, $recommendation->id);
    }

    #[Test]
    public function pending_behaves_as_expected(): void
    {
        $recommendations = Recommendation::factory()->count(3)->create();

        $response = $this->get(route('recommendations.pending'));

        $response->assertOk();
        $response->assertJsonStructure([]);
    }
}
