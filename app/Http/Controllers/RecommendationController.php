<?php

namespace App\Http\Controllers;

use App\Http\Requests\RecommendationAcknowledgeRequest;
use App\Http\Requests\RecommendationDismissRequest;
use App\Http\Resources\RecommendationCollection;
use App\Http\Resources\RecommendationResource;
use App\Models\Recommendation;
use Illuminate\Http\Request;

class RecommendationController extends Controller
{
    public function index(Request $request): RecommendationCollection
    {
        $recommendations = Recommendation::paginate();

        $recommendations = Recommendation::where('user_id', $user_id)->get();

        return new RecommendationCollection($Recommendation);
    }

    public function show(Request $request, Recommendation $recommendation): RecommendationResource
    {
        $recommendation = Recommendation::find($id);

        return new RecommendationResource($Recommendation);
    }

    public function acknowledge(RecommendationAcknowledgeRequest $request)
    {
        $recommendation->update($request->validated());

        return $Recommendation;
    }

    public function dismiss(RecommendationDismissRequest $request)
    {
        $recommendation->update($request->validated());

        return $Recommendation;
    }

    public function pending(Request $request): RecommendationCollection
    {
        $recommendations = Recommendation::where('user_id,status', $user_id, status)->get();

        return new RecommendationCollection($Recommendation);
    }
}
