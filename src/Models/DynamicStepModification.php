<?php

namespace AshiqFardus\ApprovalProcess\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DynamicStepModification extends Model
{
    protected $fillable = [
        'approval_request_id',
        'step_id',
        'modification_type',
        'old_data',
        'new_data',
        'reason',
        'modified_by_user_id',
        'is_applied',
        'applied_at',
    ];

    protected $casts = [
        'old_data' => 'array',
        'new_data' => 'array',
        'is_applied' => 'boolean',
        'applied_at' => 'datetime',
    ];

    const TYPE_ADDED = 'added';
    const TYPE_REMOVED = 'removed';
    const TYPE_MODIFIED = 'modified';
    const TYPE_SKIPPED = 'skipped';
    const TYPE_REORDERED = 'reordered';

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
     * Get the user who made the modification.
     */
    public function modifiedBy(): BelongsTo
    {
        return $this->belongsTo(config('approval-process.models.user'), 'modified_by_user_id');
    }

    /**
     * Scope to get modifications for a request.
     */
    public function scopeForRequest($query, int $requestId)
    {
        return $query->where('approval_request_id', $requestId);
    }

    /**
     * Scope to get applied modifications.
     */
    public function scopeApplied($query)
    {
        return $query->where('is_applied', true);
    }

    /**
     * Scope to get pending modifications.
     */
    public function scopePending($query)
    {
        return $query->where('is_applied', false);
    }

    /**
     * Mark as applied.
     */
    public function markAsApplied(): void
    {
        $this->update([
            'is_applied' => true,
            'applied_at' => now(),
        ]);
    }
}
