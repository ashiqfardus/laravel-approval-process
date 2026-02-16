<?php

namespace AshiqFardus\ApprovalProcess\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActiveParallelStep extends Model
{
    protected $fillable = [
        'approval_request_id',
        'step_id',
        'parallel_group_id',
        'status',
        'activated_at',
        'completed_at',
    ];

    protected $casts = [
        'activated_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';

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
     * Get the parallel group.
     */
    public function parallelGroup(): BelongsTo
    {
        return $this->belongsTo(ParallelStepGroup::class, 'parallel_group_id');
    }

    /**
     * Scope to get active steps for a request.
     */
    public function scopeForRequest($query, int $requestId)
    {
        return $query->where('approval_request_id', $requestId);
    }

    /**
     * Scope to get pending steps.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope to get in-progress steps.
     */
    public function scopeInProgress($query)
    {
        return $query->where('status', self::STATUS_IN_PROGRESS);
    }

    /**
     * Scope to get completed steps.
     */
    public function scopeCompleted($query)
    {
        return $query->whereIn('status', [self::STATUS_APPROVED, self::STATUS_REJECTED]);
    }
}
