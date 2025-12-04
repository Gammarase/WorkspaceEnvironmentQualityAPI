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
            /**
             *Enum: `'ventilate'`, `'lighting'`, `'noise'`, `'break'`,`'temperature'`, `'humidity'`
             */
            'type' => $this->type,
            'title' => $this->title,
            'message' => $this->message,
            /**
             *Enum: `'low'`, `'medium'`, `'high'`
             */
            'priority' => $this->priority,
            /**
             *Enum: `'pending'`, `'acknowledged'`, `'dismissed'`
             */
            'status' => $this->status,
            'metadata' => $this->metadata,
            'acknowledged_at' => $this->acknowledged_at,
            'dismissed_at' => $this->dismissed_at,
            'user' => UserResource::make($this->whenLoaded('user')),
        ];
    }
}
