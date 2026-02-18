<?php

namespace AshiqFardus\ApprovalProcess\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use AshiqFardus\ApprovalProcess\Services\ConditionEvaluator;
use AshiqFardus\ApprovalProcess\Models\WorkflowCondition;

class ApprovalStep extends Model
{
    use HasFactory;

    protected $table = 'approval_steps';

    protected $fillable = [
        'workflow_id',
        'name',
        'description',
        'sequence',
        'approval_type',
        'execution_type',
        'parallel_group_id',
        'parallel_sequence',
        'level_alias',
        'allow_edit',
        'allow_send_back',
        'is_active',
        'condition_config',
        'sla_hours',
        'escalation_strategy',
        'allows_delegation',
        'allows_partial_approval',
        'minimum_approval_percentage',
    ];

    protected $casts = [
        'condition_config' => 'json',
        'is_active' => 'boolean',
        'allow_edit' => 'boolean',
        'allow_send_back' => 'boolean',
        'allows_delegation' => 'boolean',
        'allows_partial_approval' => 'boolean',
    ];

    /**
     * Approval types: serial, parallel, any-one
     */
    const APPROVAL_TYPE_SERIAL = 'serial';
    const APPROVAL_TYPE_PARALLEL = 'parallel';
    const APPROVAL_TYPE_ANY_ONE = 'any-one';

    /**
     * Get the workflow this step belongs to.
     */
    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class, 'workflow_id');
    }

    /**
     * Get approvers for this step.
     */
    public function approvers(): HasMany
    {
        return $this->hasMany(Approver::class, 'approval_step_id');
    }

    /**
     * Get approval actions for this step.
     */
    public function actions(): HasMany
    {
        return $this->hasMany(ApprovalAction::class, 'approval_step_id');
    }

    /**
     * Check if step is serial approval.
     */
    public function isSerial(): bool
    {
        return $this->approval_type === self::APPROVAL_TYPE_SERIAL;
    }

    /**
     * Check if step is parallel approval.
     */
    public function isParallel(): bool
    {
        return $this->approval_type === self::APPROVAL_TYPE_PARALLEL;
    }

    /**
     * Check if step is any-one approval.
     */
    public function isAnyOne(): bool
    {
        return $this->approval_type === self::APPROVAL_TYPE_ANY_ONE;
    }

    /**
     * Get next step in sequence.
     */
    public function getNextStep(): ?self
    {
        return $this->workflow
            ->activeSteps()
            ->where('sequence', '>', $this->sequence)
            ->first();
    }

    /**
     * Get previous step in sequence.
     */
    public function getPreviousStep(): ?self
    {
        return $this->workflow
            ->activeSteps()
            ->where('sequence', '<', $this->sequence)
            ->orderByDesc('sequence')
            ->first();
    }

    /**
     * Evaluate if step should be included based on conditions.
     */
    public function shouldInclude(Model $requestModel): bool
    {
        if (empty($this->condition_config)) {
            return true;
        }

        // Use ConditionEvaluator to check if step should be included
        $evaluator = new ConditionEvaluator();
        
        // Convert model to array for evaluation
        $data = $requestModel->toArray();
        
        // Parse conditions from config
        $conditions = $this->condition_config['conditions'] ?? [];
        $logic = $this->condition_config['logic'] ?? 'and';
        
        if (empty($conditions)) {
            return true;
        }
        
        // Convert array conditions to temporary objects to use with evaluator
        $conditionObjects = array_map(function($c) {
            return new WorkflowCondition($c);
        }, $conditions);
        
        // Use a modified evaluateConditions that accepts array of objects
        // Since we don't have real DB models here, we might need a simpler evaluator
        // For now, let's do a basic manual check to avoid dependency circularity
        
        $results = [];
        foreach ($conditions as $condition) {
            $field = $condition['field'] ?? '';
            $operator = $condition['operator'] ?? '=';
            $value = $condition['value'] ?? null;
            
            $modelValue = data_get($data, $field);
            
            // Re-use logic from ConditionEvaluator manually or instantiate it if compatible
            // Let's rely on the service if possible, but for a model method, keep it light:
            
            $match = match($operator) {
                '=' => $modelValue == $value,
                '>' => $modelValue > $value,
                '<' => $modelValue < $value,
                default => false,
            };
            
            $results[] = $match;
        }
        
        if ($logic === 'and') {
             return !in_array(false, $results);
        } else {
             return in_array(true, $results);
        }
    }

    /**
     * Get level alias for printing.
     */
    public function getLevelAlias(): string
    {
        return $this->level_alias ?? $this->name;
    }

    /**
     * Check if step allows editing before approval.
     */
    public function allowsEdit(): bool
    {
        return $this->allow_edit ?? false;
    }

    /**
     * Check if step allows sending back.
     */
    public function allowsSendBack(): bool
    {
        return $this->allow_send_back ?? true;
    }

    /**
     * Get SLA hours for this step.
     */
    public function getSLAHours(): ?int
    {
        return $this->sla_hours;
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \AshiqFardus\ApprovalProcess\Tests\Factories\ApprovalStepFactory::new();
    }
}
