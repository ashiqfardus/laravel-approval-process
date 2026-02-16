<?php

namespace AshiqFardus\ApprovalProcess\Http\Controllers;

use AshiqFardus\ApprovalProcess\Services\ApprovalNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController
{
    protected ApprovalNotificationService $notificationService;

    public function __construct(ApprovalNotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Get all notifications for authenticated user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        
        $notifications = \AshiqFardus\ApprovalProcess\Models\ApprovalNotification::forUser($userId)
            ->with('approvalRequest.requestable')
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $notifications,
        ]);
    }

    /**
     * Get unread notifications for authenticated user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function unread(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        
        $notifications = $this->notificationService->getUnreadNotifications($userId);

        return response()->json([
            'success' => true,
            'data' => $notifications,
            'count' => $notifications->count(),
        ]);
    }

    /**
     * Mark a notification as read.
     *
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    public function markAsRead(int $id, Request $request): JsonResponse
    {
        $notification = \AshiqFardus\ApprovalProcess\Models\ApprovalNotification::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $this->notificationService->markAsRead($id);

        return response()->json([
            'success' => true,
            'message' => 'Notification marked as read',
        ]);
    }

    /**
     * Mark all notifications as read for authenticated user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        
        $this->notificationService->markAllAsRead($userId);

        return response()->json([
            'success' => true,
            'message' => 'All notifications marked as read',
        ]);
    }

    /**
     * Get notification count.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function count(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        
        $unreadCount = \AshiqFardus\ApprovalProcess\Models\ApprovalNotification::forUser($userId)
            ->unread()
            ->count();

        return response()->json([
            'success' => true,
            'unread_count' => $unreadCount,
        ]);
    }
}
