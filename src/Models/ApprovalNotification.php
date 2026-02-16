<?php

namespace AshiqFardus\ApprovalProcess\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApprovalNotification extends Model
{
    protected $fillable = [
        'approval_request_id',
        'user_id',
        'type',
        'message',
        'channels',
        'data',
        'is_read',
        'read_at',
        'email_sent_at',
        'sms_sent_at',
    ];

    protected $casts = [
        'channels' => 'array',
        'data' => 'array',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
        'email_sent_at' => 'datetime',
        'sms_sent_at' => 'datetime',
    ];

    /**
     * Get the approval request
     */
    public function approvalRequest(): BelongsTo
    {
        return $this->belongsTo(ApprovalRequest::class);
    }

    /**
     * Get the user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(config('approval-process.user_model', \App\Models\User::class));
    }

    /**
     * Scope for unread notifications
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * Scope for read notifications
     */
    public function scopeRead($query)
    {
        return $query->where('is_read', true);
    }

    /**
     * Scope for specific user
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for specific type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(): void
    {
        $this->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    /**
     * Mark notification as unread
     */
    public function markAsUnread(): void
    {
        $this->update([
            'is_read' => false,
            'read_at' => null,
        ]);
    }

    /**
     * Check if notification should be sent via email
     */
    public function shouldSendEmail(): bool
    {
        return in_array('email', $this->channels ?? []);
    }

    /**
     * Check if notification should be sent via SMS
     */
    public function shouldSendSMS(): bool
    {
        return in_array('sms', $this->channels ?? []);
    }

    /**
     * Check if notification should be sent via push
     */
    public function shouldSendPush(): bool
    {
        return in_array('push', $this->channels ?? []);
    }

    /**
     * Mark email as sent
     */
    public function markEmailSent(): void
    {
        $this->update(['email_sent_at' => now()]);
    }

    /**
     * Mark SMS as sent
     */
    public function markSMSSent(): void
    {
        $this->update(['sms_sent_at' => now()]);
    }
}
