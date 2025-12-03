<?php

namespace App\Http\Controllers;

use App\Http\Requests\SensorReadingCurrentRequest;
use App\Http\Requests\SensorReadingHistoryRequest;
use App\Http\Requests\SensorReadingStoreRequest;
use App\Http\Resources\SensorReadingResource;
use App\Models\Device;
use App\Models\SensorReading;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SensorReadingController extends Controller
{
    public function store(SensorReadingStoreRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $device = Device::findOrFail($validated['device_id']);

        if ($device->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized');
        }

        if (! isset($validated['reading_timestamp'])) {
            $validated['reading_timestamp'] = now();
        }

        $sensorReading = SensorReading::create($validated);

        $device->update(['last_seen_at' => now()]);

        return response()->json(new SensorReadingResource($sensorReading), 201);
    }

    public function current(SensorReadingCurrentRequest $request): SensorReadingResource
    {
        $deviceId = $request->validated('device_id');

        $device = Device::findOrFail($deviceId);

        if ($device->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized');
        }

        $sensorReading = SensorReading::where('device_id', $deviceId)
            ->latest('reading_timestamp')
            ->first();

        if (! $sensorReading) {
            abort(404, 'No readings found for this device');
        }

        return new SensorReadingResource($sensorReading);
    }

    public function history(SensorReadingHistoryRequest $request): AnonymousResourceCollection
    {
        $validated = $request->validated();

        $device = Device::findOrFail($validated['device_id']);

        if ($device->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized');
        }

        $sensorReadings = SensorReading::where('device_id', $validated['device_id'])
            ->whereBetween('reading_timestamp', [$validated['start_date'], $validated['end_date']])
            ->orderBy('reading_timestamp', 'desc')
            ->paginate(20);

        return SensorReadingResource::collection($sensorReadings);
    }
}
