<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\RegisterDeviceRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeviceController extends ApiController
{
    /** Register (or refresh) an FCM device token for push notifications. */
    public function store(RegisterDeviceRequest $request): JsonResponse
    {
        $device = $request->user()->deviceTokens()->updateOrCreate(
            ['token' => $request->validated('token')],
            ['platform' => $request->validated('platform')]
        );

        return $this->respondCreated([
            'id' => $device->id,
            'platform' => $device->platform,
        ], 'Device registered for push notifications');
    }

    public function destroy(Request $request): JsonResponse
    {
        $validated = $request->validate(['token' => 'required|string']);

        $request->user()->deviceTokens()->where('token', $validated['token'])->delete();

        return $this->respondDeleted('Device unregistered');
    }
}
