<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends ApiController
{
    /** The user's own notifications plus broadcast (user-less) ones. */
    public function index(Request $request): JsonResponse
    {
        $notifications = Notification::where(function ($q) use ($request) {
                $q->where('user_id', $request->user()->id)->orWhereNull('user_id');
            })
            ->when($request->boolean('unread_only'), fn ($q) => $q->where('read', false))
            ->latest()
            ->paginate(min((int) $request->query('limit', 20), 100));

        return $this->respond(NotificationResource::collection($notifications));
    }

    public function markRead(Request $request, Notification $notification): JsonResponse
    {
        abort_unless(
            $notification->user_id === null || $notification->user_id === $request->user()->id,
            403,
            'Not your notification'
        );

        $notification->update(['read' => true]);

        return $this->respond(new NotificationResource($notification->fresh()), 'Notification marked as read');
    }

    public function markAllRead(Request $request): JsonResponse
    {
        Notification::where(function ($q) use ($request) {
                $q->where('user_id', $request->user()->id)->orWhereNull('user_id');
            })
            ->where('read', false)
            ->update(['read' => true]);

        return $this->respond(null, 'All notifications marked as read');
    }
}
