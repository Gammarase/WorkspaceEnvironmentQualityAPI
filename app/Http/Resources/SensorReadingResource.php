<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SensorReadingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'device_id' => $this->device_id,
            'temperature' => $this->temperature,
            'humidity' => $this->humidity,
            'tvoc_ppm' => $this->tvoc_ppm,
            'light' => $this->light,
            'noise' => $this->noise,
            'reading_timestamp' => $this->reading_timestamp,
            'device' => DeviceResource::make($this->whenLoaded('device')),
        ];
    }
}
