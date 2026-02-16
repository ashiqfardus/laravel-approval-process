<?php

namespace AshiqFardus\ApprovalProcess\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class WorkflowCondition extends Model
{
    protected $fillable = [
        'workflow_id',
        'from_step_id',
        'to_step_id',
        'name',
        'field',
        'operator',
        'value',
        'logic_operator',
        'priority',
        'is_active',
    ];

    protected $casts = [
        'value' => 'array',
        'is_active' => 'boolean',
        'priority' => 'integer',
    ];

    /**
     * Supported operators for condition evaluation.
     */
    const OPERATOR_EQUALS = '=';
    const OPERATOR_NOT_EQUALS = '!=';
    const OPERATOR_GREATER_THAN = '>';
    const OPERATOR_GREATER_THAN_OR_EQUAL = '>=';
    const OPERATOR_LESS_THAN = '<';
    const OPERATOR_LESS_THAN_OR_EQUAL = '<=';
    const OPERATOR_IN = 'in';
    const OPERATOR_NOT_IN = 'not_in';
    const OPERATOR_BETWEEN = 'between';
    const OPERATOR_CONTAINS = 'contains';
    const OPERATOR_NOT_CONTAINS = 'not_contains';
    const OPERATOR_STARTS_WITH = 'starts_with';
    const OPERATOR_ENDS_WITH = 'ends_with';
    const OPERATOR_IS_NULL = 'is_null';
    const OPERATOR_IS_NOT_NULL = 'is_not_null';

    /**
     * Get the workflow this condition belongs to.
     */
    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class, 'workflow_id');
    }

    /**
     * Get the source step.
     */
    public function fromStep(): BelongsTo
    {
        return $this->belongsTo(ApprovalStep::class, 'from_step_id');
    }

    /**
     * Get the target step.
     */
    public function toStep(): BelongsTo
    {
        return $this->belongsTo(ApprovalStep::class, 'to_step_id');
    }

    /**
     * Get the condition groups this condition belongs to.
     */
    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(
            WorkflowConditionGroup::class,
            'workflow_condition_group_items',
            'condition_id',
            'group_id'
        )->withPivot('sequence')->orderBy('sequence');
    }

    /**
     * Scope to get active conditions.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get conditions for a specific workflow.
     */
    public function scopeForWorkflow($query, int $workflowId)
    {
        return $query->where('workflow_id', $workflowId);
    }

    /**
     * Scope to get conditions from a specific step.
     */
    public function scopeFromStep($query, int $stepId)
    {
        return $query->where('from_step_id', $stepId);
    }

    /**
     * Get all supported operators.
     */
    public static function getSupportedOperators(): array
    {
        return [
            self::OPERATOR_EQUALS,
            self::OPERATOR_NOT_EQUALS,
            self::OPERATOR_GREATER_THAN,
            self::OPERATOR_GREATER_THAN_OR_EQUAL,
            self::OPERATOR_LESS_THAN,
            self::OPERATOR_LESS_THAN_OR_EQUAL,
            self::OPERATOR_IN,
            self::OPERATOR_NOT_IN,
            self::OPERATOR_BETWEEN,
            self::OPERATOR_CONTAINS,
            self::OPERATOR_NOT_CONTAINS,
            self::OPERATOR_STARTS_WITH,
            self::OPERATOR_ENDS_WITH,
            self::OPERATOR_IS_NULL,
            self::OPERATOR_IS_NOT_NULL,
        ];
    }
}
