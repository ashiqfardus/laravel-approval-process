<?php

namespace AshiqFardus\ApprovalProcess\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowVersion extends Model
{
    protected $fillable = [
        'workflow_id',
        'version_number',
        'workflow_snapshot',
        'steps_snapshot',
        'change_type',
        'change_description',
        'changed_by_user_id',
        'is_active',
    ];

    protected $casts = [
        'workflow_snapshot' => 'array',
        'steps_snapshot' => 'array',
        'is_active' => 'boolean',
        'version_number' => 'integer',
    ];

    const CHANGE_TYPE_CREATED = 'created';
    const CHANGE_TYPE_STEP_ADDED = 'step_added';
    const CHANGE_TYPE_STEP_REMOVED = 'step_removed';
    const CHANGE_TYPE_STEP_MODIFIED = 'step_modified';
    const CHANGE_TYPE_APPROVER_CHANGED = 'approver_changed';
    const CHANGE_TYPE_CONDITION_CHANGED = 'condition_changed';
    const CHANGE_TYPE_PARALLEL_CHANGED = 'parallel_changed';

    /**
     * Get the workflow.
     */
    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class, 'workflow_id');
    }

    /**
     * Get the user who made the change.
     */
    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(config('approval-process.models.user'), 'changed_by_user_id');
    }

    /**
     * Scope to get active version.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get versions for a workflow.
     */
    public function scopeForWorkflow($query, int $workflowId)
    {
        return $query->where('workflow_id', $workflowId);
    }

    /**
     * Get the latest version number for a workflow.
     */
    public static function getLatestVersionNumber(int $workflowId): int
    {
        return static::forWorkflow($workflowId)->max('version_number') ?? 0;
    }

    /**
     * Create a snapshot of the current workflow state.
     */
    public static function createSnapshot(
        Workflow $workflow,
        string $changeType,
        int $userId,
        ?string $description = null
    ): self {
        $versionNumber = static::getLatestVersionNumber($workflow->id) + 1;

        // Deactivate previous versions
        static::forWorkflow($workflow->id)->update(['is_active' => false]);

        return static::create([
            'workflow_id' => $workflow->id,
            'version_number' => $versionNumber,
            'workflow_snapshot' => $workflow->toArray(),
            'steps_snapshot' => $workflow->steps()->with('approvers')->get()->toArray(),
            'change_type' => $changeType,
            'change_description' => $description,
            'changed_by_user_id' => $userId,
            'is_active' => true,
        ]);
    }
}
