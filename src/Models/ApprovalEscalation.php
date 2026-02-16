<?php

namespace AshiqFardus\ApprovalProcess\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApprovalEscalation extends Model
{
    protected $fillable = [
        'approval_request_id',
        'from_user_id',
        'to_user_id',
        'from_level',
        'to_level',
        'reason',
        'remarks',
    ];

    /**
     * Get the approval request
     */
    public function approvalRequest(): BelongsTo
    {
        return $this->belongsTo(ApprovalRequest::class);
    }

    /**
     * Get the user who was originally assigned
     */
    public function fromUser(): BelongsTo
    {
        return $this->belongsTo(config('approval-process.user_model', \App\Models\User::class), 'from_user_id');
    }

    /**
     * Get the user who received the escalation
     */
    public function toUser(): BelongsTo
    {
        return $this->belongsTo(config('approval-process.user_model', \App\Models\User::class), 'to_user_id');
    }

    /**
     * Scope for SLA timeout escalations
     */
    public function scopeSlaTimeout($query)
    {
        return $query->where('reason', 'sla_timeout');
    }

    /**
     * Scope for manual escalations
     */
    public function scopeManual($query)
    {
        return $query->where('reason', 'manual');
    }

    /**
     * Scope for auto escalations
     */
    public function scopeAuto($query)
    {
        return $query->where('reason', 'auto');
    }
}
