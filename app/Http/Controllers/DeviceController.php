<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeviceStoreRequest;
use App\Http\Requests\DeviceUpdateRequest;
use App\Http\Resources\DeviceCollection;
use App\Http\Resources\DeviceResource;
use App\Models\Device;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class DeviceController extends Controller
{
    /**
     * @response array{
     *   data: DeviceResource[],
     *   links: array{
     *     first: string,
     *     last: string,
     *     prev: string|null,
     *     next: string|null
     *   },
     *   meta: array{
     *     current_page: int,
     *     from: int,
     *     last_page: int,
     *     path: string,
     *     per_page: int,
     *     to: int,
     *     total: int
     *   }
     * }
     */
    public function index(Request $request): DeviceCollection
    {
        $devices = $request->user()->devices()->with('recommendations')->paginate(10);

        return new DeviceCollection($devices);
    }

    public function store(DeviceStoreRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $validated['user_id'] = $request->user()->id;

        $device = Device::create($validated)->fresh();

        return response()->json(new DeviceResource($device), 201);
    }

    public function show(Request $request, Device $device): DeviceResource
    {
        if ($device->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized');
        }

        return new DeviceResource($device);
    }

    public function update(DeviceUpdateRequest $request, Device $device): DeviceResource
    {
        if ($device->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized');
        }

        $device->update($request->validated());

        return new DeviceResource($device);
    }

    public function destroy(Request $request, Device $device): Response
    {
        if ($device->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized');
        }

        $device->delete();

        return response()->noContent();
    }
}
