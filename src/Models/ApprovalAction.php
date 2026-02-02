<?php

namespace AshiqFardus\ApprovalProcess\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApprovalAction extends Model
{
    protected $table = 'approval_actions';

    protected $fillable = [
        'approval_request_id',
        'approval_step_id',
        'user_id',
        'action',
        'remarks',
        'attachments',
        'metadata',
        'ip_address',
        'device_info',
        'action_at',
    ];

    protected $casts = [
        'attachments' => 'json',
        'metadata' => 'json',
        'action_at' => 'datetime',
    ];

    // Action types
    const ACTION_APPROVED = 'approved';
    const ACTION_REJECTED = 'rejected';
    const ACTION_SENT_BACK = 'sent-back';
    const ACTION_HELD = 'held';
    const ACTION_ESCALATED = 'escalated';
    const ACTION_DELEGATED = 'delegated';
    const ACTION_COMMENTED = 'commented';

    /**
     * Get the approval request.
     */
    public function request(): BelongsTo
    {
        return $this->belongsTo(ApprovalRequest::class, 'approval_request_id');
    }

    /**
     * Get the approval step.
     */
    public function step(): BelongsTo
    {
        return $this->belongsTo(ApprovalStep::class, 'approval_step_id');
    }

    /**
     * Get the user who performed the action.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'user_id');
    }

    /**
     * Record the action with metadata.
     */
    public static function recordAction(
        ApprovalRequest $request,
        ApprovalStep $step,
        int $userId,
        string $action,
        ?string $remarks = null,
        array $metadata = []
    ): self {
        return static::create([
            'approval_request_id' => $request->id,
            'approval_step_id' => $step->id,
            'user_id' => $userId,
            'action' => $action,
            'remarks' => $remarks,
            'metadata' => $metadata,
            'ip_address' => request()->ip(),
            'device_info' => request()->userAgent(),
            'action_at' => now(),
        ]);
    }
}
