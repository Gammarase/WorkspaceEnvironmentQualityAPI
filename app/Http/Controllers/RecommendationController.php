<?php

namespace App\Http\Controllers;

use App\Http\Requests\RecommendationAcknowledgeRequest;
use App\Http\Requests\RecommendationDismissRequest;
use App\Http\Resources\RecommendationCollection;
use App\Http\Resources\RecommendationResource;
use App\Models\Recommendation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class RecommendationController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $recommendations = $request->user()->recommendations()
            ->with('device')
            ->orderByRaw("CASE priority WHEN 'high' THEN 1 WHEN 'medium' THEN 2 WHEN 'low' THEN 3 END")
            ->latest()
            ->paginate();

        return RecommendationResource::collection($recommendations);
    }

    public function show(Request $request, Recommendation $recommendation): RecommendationResource
    {
        if ($recommendation->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized');
        }

        return new RecommendationResource($recommendation);
    }

    public function acknowledge(RecommendationAcknowledgeRequest $request): RecommendationResource
    {
        $recommendation = Recommendation::findOrFail($request->validated('id'));

        if ($recommendation->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized');
        }

        $recommendation->update([
            'status' => 'acknowledged',
            'acknowledged_at' => now(),
        ]);

        return new RecommendationResource($recommendation);
    }

    public function dismiss(RecommendationDismissRequest $request): RecommendationResource
    {
        $recommendation = Recommendation::findOrFail($request->validated('id'));

        if ($recommendation->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized');
        }

        $recommendation->update([
            'status' => 'dismissed',
            'dismissed_at' => now(),
        ]);

        return new RecommendationResource($recommendation);
    }

    public function pending(Request $request): RecommendationCollection
    {
        $recommendations = $request->user()->recommendations()
            ->where('status', 'pending')
            ->orderByRaw("CASE priority WHEN 'high' THEN 1 WHEN 'medium' THEN 2 WHEN 'low' THEN 3 END")
            ->latest()
            ->get();

        return new RecommendationCollection($recommendations);
    }
}
