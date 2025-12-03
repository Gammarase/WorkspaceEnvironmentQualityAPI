<?php

namespace App\Http\Controllers;

use App\Http\Requests\SensorReadingCurrentRequest;
use App\Http\Requests\SensorReadingHistoryRequest;
use App\Http\Requests\SensorReadingStoreRequest;
use App\Http\Resources\SensorReadingResource;
use App\Models\SensorReading;
use Illuminate\Http\Response;

class SensorReadingController extends Controller
{
    public function store(SensorReadingStoreRequest $request): Response
    {
        $sensorReading = SensorReading::create($request->validated());

        return response()->noContent(201);
    }

    public function current(SensorReadingCurrentRequest $request): SensorReadingResource
    {
        $sensorReadings = SensorReading::where('device_id', $device_id)->get();

        return new SensorReadingResource($SensorReading);
    }

    public function history(SensorReadingHistoryRequest $request): SensorReadingResource
    {
        $sensorReadings = SensorReading::where('device_id,', $device_id)->whereBetween(reading_timestamp)->get();

        return new SensorReadingResource($SensorReading);
    }
}
