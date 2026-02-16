<?php

namespace AshiqFardus\ApprovalProcess\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParallelExecutionState extends Model
{
    protected $fillable = [
        'approval_request_id',
        'parallel_group_id',
        'status',
        'completed_steps',
        'total_steps',
        'step_statuses',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'completed_steps' => 'integer',
        'total_steps' => 'integer',
        'step_statuses' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';

    /**
     * Get the approval request.
     */
    public function approvalRequest(): BelongsTo
    {
        return $this->belongsTo(ApprovalRequest::class, 'approval_request_id');
    }

    /**
     * Get the parallel group.
     */
    public function parallelGroup(): BelongsTo
    {
        return $this->belongsTo(ParallelStepGroup::class, 'parallel_group_id');
    }

    /**
     * Mark a step as completed.
     */
    public function markStepCompleted(int $stepId, string $status = 'approved'): void
    {
        $statuses = $this->step_statuses ?? [];
        $statuses[$stepId] = [
            'status' => $status,
            'completed_at' => now()->toIso8601String(),
        ];

        $this->update([
            'step_statuses' => $statuses,
            'completed_steps' => $this->completed_steps + 1,
        ]);
    }

    /**
     * Check if execution is complete.
     */
    public function isComplete(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if sync condition is met.
     */
    public function isSyncConditionMet(): bool
    {
        return $this->parallelGroup->isSyncConditionMet(
            $this->completed_steps,
            $this->total_steps
        );
    }

    /**
     * Get completion percentage.
     */
    public function getCompletionPercentage(): float
    {
        if ($this->total_steps === 0) {
            return 0;
        }

        return round(($this->completed_steps / $this->total_steps) * 100, 2);
    }
}
