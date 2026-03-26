<?php

namespace App\Http\Controllers;

use App\Models\AppNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Get notifications for the authenticated user.
     * Returns latest 50 notifications + unread count.
     */
    public function index(): JsonResponse
    {
        $userId = Auth::id();

        $notifications = AppNotification::forUser($userId)
            ->with('actor:id,name,role')
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->map(function ($n) {
                return [
                    'id'             => $n->id,
                    'type'           => $n->type,
                    'message'        => $n->message,
                    'user_name'      => $n->actor?->name ?? 'System',
                    'user_role'      => $n->actor?->role ?? 'system',
                    'resource_type'  => $n->resource_type,
                    'resource_id'    => $n->resource_id,
                    'resource_label' => $n->resource_label,
                    'redirect_url'   => $n->redirect_url,
                    'is_read'        => $n->is_read,
                    'created_at'     => $n->created_at->toIso8601String(),
                    'time_ago'       => $n->time_ago,
                ];
            });

        $unreadCount = AppNotification::forUser($userId)->unread()->count();

        return response()->json([
            'success'      => true,
            'data'         => $notifications,
            'unread_count' => $unreadCount,
        ]);
    }

    /**
     * Mark a single notification as read.
     */
    public function markAsRead(int $id): JsonResponse
    {
        $notification = AppNotification::forUser(Auth::id())->findOrFail($id);
        $notification->update(['is_read' => true]);

        return response()->json(['success' => true]);
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead(): JsonResponse
    {
        AppNotification::forUser(Auth::id())
            ->unread()
            ->update(['is_read' => true]);

        return response()->json(['success' => true]);
    }

    /**
     * Clear all notifications.
     */
    public function clear(): JsonResponse
    {
        AppNotification::forUser(Auth::id())->delete();

        return response()->json(['success' => true]);
    }
}
