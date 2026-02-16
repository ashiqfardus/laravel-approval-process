<?php

namespace AshiqFardus\ApprovalProcess\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DynamicApproverAssignment extends Model
{
    protected $fillable = [
        'approval_request_id',
        'step_id',
        'original_approver_id',
        'new_approver_id',
        'assignment_type',
        'reason',
        'assigned_by_user_id',
        'valid_from',
        'valid_until',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
    ];

    const TYPE_REPLACEMENT = 'replacement';
    const TYPE_ADDITION = 'addition';
    const TYPE_TEMPORARY = 'temporary';

    /**
     * Get the approval request.
     */
    public function approvalRequest(): BelongsTo
    {
        return $this->belongsTo(ApprovalRequest::class, 'approval_request_id');
    }

    /**
     * Get the step.
     */
    public function step(): BelongsTo
    {
        return $this->belongsTo(ApprovalStep::class, 'step_id');
    }

    /**
     * Get the original approver.
     */
    public function originalApprover(): BelongsTo
    {
        return $this->belongsTo(config('approval-process.models.user'), 'original_approver_id');
    }

    /**
     * Get the new approver.
     */
    public function newApprover(): BelongsTo
    {
        return $this->belongsTo(config('approval-process.models.user'), 'new_approver_id');
    }

    /**
     * Get the user who made the assignment.
     */
    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(config('approval-process.models.user'), 'assigned_by_user_id');
    }

    /**
     * Scope to get active assignments.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('valid_until')
                  ->orWhere('valid_until', '>', now());
            });
    }

    /**
     * Scope to get assignments for a request.
     */
    public function scopeForRequest($query, int $requestId)
    {
        return $query->where('approval_request_id', $requestId);
    }

    /**
     * Check if assignment is currently valid.
     */
    public function isValid(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $now = now();

        if ($this->valid_from && $now->lt($this->valid_from)) {
            return false;
        }

        if ($this->valid_until && $now->gt($this->valid_until)) {
            return false;
        }

        return true;
    }
}
