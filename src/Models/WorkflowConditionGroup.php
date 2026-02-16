<?php

namespace AshiqFardus\ApprovalProcess\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class WorkflowConditionGroup extends Model
{
    protected $fillable = [
        'workflow_id',
        'name',
        'logic_operator',
        'priority',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'priority' => 'integer',
    ];

    const LOGIC_AND = 'and';
    const LOGIC_OR = 'or';

    /**
     * Get the workflow this group belongs to.
     */
    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class, 'workflow_id');
    }

    /**
     * Get the conditions in this group.
     */
    public function conditions(): BelongsToMany
    {
        return $this->belongsToMany(
            WorkflowCondition::class,
            'workflow_condition_group_items',
            'group_id',
            'condition_id'
        )->withPivot('sequence')->orderBy('sequence');
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
}
