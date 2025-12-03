<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RecommendationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'device_id' => $this->device_id,
            'user_id' => $this->user_id,
            'type' => $this->type,
            'title' => $this->title,
            'message' => $this->message,
            'priority' => $this->priority,
            'status' => $this->status,
            'metadata' => $this->metadata,
            'acknowledged_at' => $this->acknowledged_at,
            'dismissed_at' => $this->dismissed_at,
            'user' => UserResource::make($this->whenLoaded('user')),
        ];
    }
}
