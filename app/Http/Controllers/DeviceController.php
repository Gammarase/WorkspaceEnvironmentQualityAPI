<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeviceStoreRequest;
use App\Http\Requests\DeviceUpdateRequest;
use App\Http\Resources\DeviceCollection;
use App\Http\Resources\DeviceResource;
use App\Models\Device;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class DeviceController extends Controller
{
    public function index(Request $request): DeviceCollection
    {
        $devices = Device::where('user_id', $user_id)->get();

        return new DeviceCollection($Device);
    }

    public function store(DeviceStoreRequest $request): DeviceResource
    {
        $device = Device::create($request->validated());

        return new DeviceResource($Device);
    }

    public function show(Request $request, Device $device): DeviceResource
    {
        $device = Device::find($id);

        return new DeviceResource($Device);
    }

    public function update(DeviceUpdateRequest $request, Device $device): DeviceResource
    {
        $device->update($request->validated());

        return new DeviceResource($Device);
    }

    public function destroy(Request $request, Device $device): Response
    {
        $device->delete();

        return response()->noContent();
    }
}
