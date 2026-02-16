<?php

namespace AshiqFardus\ApprovalProcess\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowModificationRule extends Model
{
    protected $fillable = [
        'workflow_id',
        'rule_type',
        'conditions',
        'restrictions',
        'requires_approval',
        'approval_required_from_user_id',
        'is_active',
    ];

    protected $casts = [
        'conditions' => 'array',
        'restrictions' => 'array',
        'requires_approval' => 'boolean',
        'is_active' => 'boolean',
    ];

    const RULE_ALLOW_STEP_ADDITION = 'allow_step_addition';
    const RULE_ALLOW_STEP_REMOVAL = 'allow_step_removal';
    const RULE_ALLOW_APPROVER_CHANGE = 'allow_approver_change';
    const RULE_ALLOW_REORDERING = 'allow_reordering';
    const RULE_ALLOW_SKIP_STEP = 'allow_skip_step';

    /**
     * Get the workflow.
     */
    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class, 'workflow_id');
    }

    /**
     * Get the user whose approval is required.
     */
    public function approvalRequiredFrom(): BelongsTo
    {
        return $this->belongsTo(config('approval-process.models.user'), 'approval_required_from_user_id');
    }

    /**
     * Scope to get active rules.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get rules for a workflow.
     */
    public function scopeForWorkflow($query, int $workflowId)
    {
        return $query->where('workflow_id', $workflowId);
    }

    /**
     * Scope to get rules by type.
     */
    public function scopeByType($query, string $ruleType)
    {
        return $query->where('rule_type', $ruleType);
    }

    /**
     * Get all supported rule types.
     */
    public static function getRuleTypes(): array
    {
        return [
            self::RULE_ALLOW_STEP_ADDITION,
            self::RULE_ALLOW_STEP_REMOVAL,
            self::RULE_ALLOW_APPROVER_CHANGE,
            self::RULE_ALLOW_REORDERING,
            self::RULE_ALLOW_SKIP_STEP,
        ];
    }
}
