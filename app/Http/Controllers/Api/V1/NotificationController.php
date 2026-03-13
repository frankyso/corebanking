<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\MobileNotificationResource;
use App\Models\MobileNotification;
use App\Models\MobileUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class NotificationController extends Controller
{
    /**
     * List all notifications for the authenticated mobile user (paginated).
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $notifications = $this->mobileUser($request)
            ->notifications()
            ->latest()
            ->paginate(20);

        return MobileNotificationResource::collection($notifications);
    }

    /**
     * Get the count of unread notifications.
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $count = $this->mobileUser($request)
            ->notifications()
            ->where('is_read', false)
            ->count();

        return response()->json(['data' => ['unread_count' => $count]]);
    }

    /**
     * Mark a single notification as read.
     */
    public function markRead(Request $request, MobileNotification $mobileNotification): MobileNotificationResource
    {
        $mobileUser = $this->mobileUser($request);

        if ($mobileNotification->mobile_user_id !== $mobileUser->id) {
            abort(403);
        }

        $mobileNotification->markAsRead();

        return MobileNotificationResource::make($mobileNotification->fresh());
    }

    /**
     * Mark all unread notifications as read.
     */
    public function markAllRead(Request $request): JsonResponse
    {
        $this->mobileUser($request)
            ->notifications()
            ->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);

        return response()->json(['message' => 'Semua notifikasi telah dibaca.']);
    }

    private function mobileUser(Request $request): MobileUser
    {
        /** @var MobileUser */
        return $request->user('mobile');
    }
}
