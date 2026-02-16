<?php

namespace AshiqFardus\ApprovalProcess\Services;

use AshiqFardus\ApprovalProcess\Models\ApprovalNotification;
use AshiqFardus\ApprovalProcess\Models\ApprovalRequest;
use Illuminate\Support\Collection;

class ApprovalNotificationService
{
    /**
     * Notify approvers about a pending approval.
     *
     * @param ApprovalRequest $request
     * @param string $type
     * @param string|null $customMessage
     * @param array $channels Notification channels: ['email', 'sms', 'real-time', 'push']
     * @return void
     */
    public function notifyApprovers(
        ApprovalRequest $request,
        string $type = 'pending',
        ?string $customMessage = null,
        array $channels = ['real-time']
    ): void {
        $approvers = $request->getPendingApprovers();

        foreach ($approvers as $approver) {
            $message = $customMessage ?? $this->getDefaultMessage($type, $request);

            $notification = ApprovalNotification::create([
                'approval_request_id' => $request->id,
                'user_id' => $approver->user_id,
                'type' => $type,
                'message' => $message,
                'channels' => $channels,
                'data' => [
                    'request_id' => $request->id,
                    'requestable_type' => $request->requestable_type,
                    'requestable_id' => $request->requestable_id,
                    'current_step' => $request->currentStep?->name,
                ],
            ]);

            // Send via configured channels
            $this->sendViaChannels($notification);
        }
    }

    /**
     * Notify creator about approval status change.
     *
     * @param ApprovalRequest $request
     * @param string $type
     * @param string|null $message
     * @param array $channels Notification channels
     * @return void
     */
    public function notifyCreator(
        ApprovalRequest $request,
        string $type,
        ?string $message = null,
        array $channels = ['real-time', 'email']
    ): void {
        $message = $message ?? $this->getDefaultMessage($type, $request);

        $notification = ApprovalNotification::create([
            'approval_request_id' => $request->id,
            'user_id' => $request->requested_by_user_id,
            'type' => $type,
            'message' => $message,
            'channels' => $channels,
            'data' => [
                'request_id' => $request->id,
                'status' => $request->status,
            ],
        ]);

        // Send via configured channels
        $this->sendViaChannels($notification);
    }

    /**
     * Get unread notifications for a user.
     *
     * @param int $userId
     * @return Collection
     */
    public function getUnreadNotifications(int $userId): Collection
    {
        return ApprovalNotification::forUser($userId)
            ->unread()
            ->with('approvalRequest.requestable')
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Mark notification as read.
     *
     * @param int $notificationId
     * @return void
     */
    public function markAsRead(int $notificationId): void
    {
        $notification = ApprovalNotification::find($notificationId);
        if ($notification) {
            $notification->markAsRead();
        }
    }

    /**
     * Mark all notifications as read for a user.
     *
     * @param int $userId
     * @return void
     */
    public function markAllAsRead(int $userId): void
    {
        ApprovalNotification::forUser($userId)
            ->unread()
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
    }

    /**
     * Get default message based on notification type.
     *
     * @param string $type
     * @param ApprovalRequest $request
     * @return string
     */
    protected function getDefaultMessage(string $type, ApprovalRequest $request): string
    {
        $messages = [
            'pending' => "New approval request awaiting your review at {$request->currentStep?->name}",
            'approved' => "Your request has been approved at {$request->currentStep?->name}",
            'rejected' => "Your request has been rejected",
            'sent_back' => "Your request has been sent back for revision",
            'edited' => "Request has been edited and resubmitted",
            'escalated' => "Approval request has been escalated to you",
            'reminder' => "Reminder: Approval request pending your review",
        ];

        return $messages[$type] ?? "Approval status update";
    }

    /**
     * Send reminder notifications for overdue approvals.
     *
     * @param ApprovalRequest $request
     * @return void
     */
    public function sendReminder(ApprovalRequest $request): void
    {
        $this->notifyApprovers($request, 'reminder', null, ['real-time', 'email']);
        $request->update(['last_reminder_sent' => now()]);
    }

    /**
     * Send notification via configured channels.
     *
     * @param ApprovalNotification $notification
     * @return void
     */
    protected function sendViaChannels(ApprovalNotification $notification): void
    {
        // Real-time notifications are stored in database (already done)
        
        // Send email if configured
        if ($notification->shouldSendEmail()) {
            $this->sendEmail($notification);
        }

        // Send SMS if configured
        if ($notification->shouldSendSMS()) {
            $this->sendSMS($notification);
        }

        // Send push notification if configured
        if ($notification->shouldSendPush()) {
            $this->sendPush($notification);
        }
    }

    /**
     * Send email notification.
     *
     * @param ApprovalNotification $notification
     * @return void
     */
    protected function sendEmail(ApprovalNotification $notification): void
    {
        // TODO: Implement email sending via Laravel Mail
        // Example:
        // Mail::to($notification->user->email)->send(new ApprovalNotificationMail($notification));
        
        $notification->markEmailSent();
    }

    /**
     * Send SMS notification.
     *
     * @param ApprovalNotification $notification
     * @return void
     */
    protected function sendSMS(ApprovalNotification $notification): void
    {
        // TODO: Implement SMS sending via Twilio/Nexmo/etc
        // Example:
        // SMS::send($notification->user->phone, $notification->message);
        
        $notification->markSMSSent();
    }

    /**
     * Send push notification.
     *
     * @param ApprovalNotification $notification
     * @return void
     */
    protected function sendPush(ApprovalNotification $notification): void
    {
        // TODO: Implement push notification via FCM/APNS
        // Example:
        // PushNotification::send($notification->user->device_token, $notification->message);
    }
}
