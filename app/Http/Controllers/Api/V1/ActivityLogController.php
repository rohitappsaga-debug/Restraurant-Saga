<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\ActivityLogResource;
use App\Models\ActivityLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActivityLogController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $logs = ActivityLog::with('user')
            ->when($request->query('user_id'), fn ($q, $id) => $q->where('user_id', $id))
            ->latest()
            ->paginate(min((int) $request->query('limit', 25), 100));

        return $this->respond(ActivityLogResource::collection($logs));
    }
}
