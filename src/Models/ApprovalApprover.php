<?php

namespace AshiqFardus\ApprovalProcess\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApprovalApprover extends Model
{
    protected $fillable = [
        'approval_step_id',
        'approver_type',
        'approver_id',
        'user_id',
        'is_approved',
        'approval_at',
        'sequence',
    ];

    protected $casts = [
        'is_approved' => 'boolean',
        'approval_at' => 'datetime',
    ];

    /**
     * Get the approval step.
     */
    public function approvalStep(): BelongsTo
    {
        return $this->belongsTo(ApprovalStep::class);
    }

    /**
     * Get the user.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model', 'App\\Models\\User'), 'user_id');
    }
}
