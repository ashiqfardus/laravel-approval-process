<?php

namespace AshiqFardus\ApprovalProcess\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ParallelStepGroup extends Model
{
    protected $fillable = [
        'workflow_id',
        'name',
        'sync_type',
        'required_approvals',
        'fork_from_step_id',
        'join_to_step_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'required_approvals' => 'integer',
    ];

    /**
     * Synchronization types.
     */
    const SYNC_ALL = 'all';           // Wait for all parallel steps to complete
    const SYNC_ANY = 'any';           // Continue when any step completes
    const SYNC_MAJORITY = 'majority'; // Continue when majority completes
    const SYNC_CUSTOM = 'custom';     // Custom number of required approvals

    /**
     * Get the workflow this group belongs to.
     */
    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class, 'workflow_id');
    }

    /**
     * Get the fork point step.
     */
    public function forkFromStep(): BelongsTo
    {
        return $this->belongsTo(ApprovalStep::class, 'fork_from_step_id');
    }

    /**
     * Get the join point step.
     */
    public function joinToStep(): BelongsTo
    {
        return $this->belongsTo(ApprovalStep::class, 'join_to_step_id');
    }

    /**
     * Get the steps in this parallel group.
     */
    public function steps(): HasMany
    {
        return $this->hasMany(ApprovalStep::class, 'parallel_group_id')
            ->orderBy('parallel_sequence');
    }

    /**
     * Get execution states for this group.
     */
    public function executionStates(): HasMany
    {
        return $this->hasMany(ParallelExecutionState::class, 'parallel_group_id');
    }

    /**
     * Scope to get active groups.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get groups for a specific workflow.
     */
    public function scopeForWorkflow($query, int $workflowId)
    {
        return $query->where('workflow_id', $workflowId);
    }

    /**
     * Check if synchronization condition is met.
     */
    public function isSyncConditionMet(int $completedSteps, int $totalSteps): bool
    {
        return match ($this->sync_type) {
            self::SYNC_ALL => $completedSteps >= $totalSteps,
            self::SYNC_ANY => $completedSteps >= 1,
            self::SYNC_MAJORITY => $completedSteps >= ceil($totalSteps / 2),
            self::SYNC_CUSTOM => $completedSteps >= ($this->required_approvals ?? $totalSteps),
            default => $completedSteps >= $totalSteps,
        };
    }

    /**
     * Get all supported sync types.
     */
    public static function getSyncTypes(): array
    {
        return [
            self::SYNC_ALL,
            self::SYNC_ANY,
            self::SYNC_MAJORITY,
            self::SYNC_CUSTOM,
        ];
    }
}
