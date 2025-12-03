<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeviceResource extends JsonResource
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
            'name' => $this->name,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'last_seen_at' => $this->last_seen_at,
            'user' => UserResource::make($this->whenLoaded('user')),
            'recommendations' => RecommendationCollection::make($this->whenLoaded('recommendations')),
        ];
    }
}
