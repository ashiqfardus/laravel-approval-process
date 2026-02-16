<?php

namespace AshiqFardus\ApprovalProcess\Services;

use AshiqFardus\ApprovalProcess\Models\WorkflowCondition;
use AshiqFardus\ApprovalProcess\Models\WorkflowConditionGroup;
use AshiqFardus\ApprovalProcess\Models\ApprovalRequest;
use Illuminate\Support\Arr;

class ConditionEvaluator
{
    /**
     * Evaluate a single condition against request data.
     */
    public function evaluateCondition(WorkflowCondition $condition, array $data): bool
    {
        $fieldValue = Arr::get($data, $condition->field);
        $compareValue = $condition->value;

        return match ($condition->operator) {
            WorkflowCondition::OPERATOR_EQUALS => $this->equals($fieldValue, $compareValue),
            WorkflowCondition::OPERATOR_NOT_EQUALS => !$this->equals($fieldValue, $compareValue),
            WorkflowCondition::OPERATOR_GREATER_THAN => $this->greaterThan($fieldValue, $compareValue),
            WorkflowCondition::OPERATOR_GREATER_THAN_OR_EQUAL => $this->greaterThanOrEqual($fieldValue, $compareValue),
            WorkflowCondition::OPERATOR_LESS_THAN => $this->lessThan($fieldValue, $compareValue),
            WorkflowCondition::OPERATOR_LESS_THAN_OR_EQUAL => $this->lessThanOrEqual($fieldValue, $compareValue),
            WorkflowCondition::OPERATOR_IN => $this->in($fieldValue, $compareValue),
            WorkflowCondition::OPERATOR_NOT_IN => !$this->in($fieldValue, $compareValue),
            WorkflowCondition::OPERATOR_BETWEEN => $this->between($fieldValue, $compareValue),
            WorkflowCondition::OPERATOR_CONTAINS => $this->contains($fieldValue, $compareValue),
            WorkflowCondition::OPERATOR_NOT_CONTAINS => !$this->contains($fieldValue, $compareValue),
            WorkflowCondition::OPERATOR_STARTS_WITH => $this->startsWith($fieldValue, $compareValue),
            WorkflowCondition::OPERATOR_ENDS_WITH => $this->endsWith($fieldValue, $compareValue),
            WorkflowCondition::OPERATOR_IS_NULL => is_null($fieldValue),
            WorkflowCondition::OPERATOR_IS_NOT_NULL => !is_null($fieldValue),
            default => false,
        };
    }

    /**
     * Evaluate multiple conditions with a logic operator.
     */
    public function evaluateConditions(array $conditions, array $data, string $logicOperator = 'and'): bool
    {
        if (empty($conditions)) {
            return true;
        }

        $results = array_map(
            fn($condition) => $this->evaluateCondition($condition, $data),
            $conditions
        );

        return $logicOperator === 'and'
            ? !in_array(false, $results, true)
            : in_array(true, $results, true);
    }

    /**
     * Evaluate a condition group.
     */
    public function evaluateGroup(WorkflowConditionGroup $group, array $data): bool
    {
        $conditions = $group->conditions()->active()->get();
        
        if ($conditions->isEmpty()) {
            return true;
        }

        return $this->evaluateConditions(
            $conditions->all(),
            $data,
            $group->logic_operator
        );
    }

    /**
     * Find the next step based on conditions from the current step.
     */
    public function findNextStep(int $workflowId, int $currentStepId, array $data): ?int
    {
        // Get all active conditions from the current step, ordered by priority
        $conditions = WorkflowCondition::active()
            ->forWorkflow($workflowId)
            ->fromStep($currentStepId)
            ->orderBy('priority', 'desc')
            ->get();

        foreach ($conditions as $condition) {
            if ($this->evaluateCondition($condition, $data)) {
                return $condition->to_step_id;
            }
        }

        return null; // No matching condition, use default routing
    }

    /**
     * Get all possible next steps from current step with their conditions.
     */
    public function getPossibleNextSteps(int $workflowId, int $currentStepId): array
    {
        $conditions = WorkflowCondition::active()
            ->forWorkflow($workflowId)
            ->fromStep($currentStepId)
            ->with('toStep')
            ->orderBy('priority', 'desc')
            ->get();

        return $conditions->map(function ($condition) {
            return [
                'step_id' => $condition->to_step_id,
                'step_name' => $condition->toStep?->name,
                'condition' => [
                    'id' => $condition->id,
                    'name' => $condition->name,
                    'field' => $condition->field,
                    'operator' => $condition->operator,
                    'value' => $condition->value,
                ],
            ];
        })->toArray();
    }

    /**
     * Validate condition configuration.
     */
    public function validateCondition(array $conditionData): array
    {
        $errors = [];

        if (empty($conditionData['field'])) {
            $errors[] = 'Field is required';
        }

        if (empty($conditionData['operator'])) {
            $errors[] = 'Operator is required';
        } elseif (!in_array($conditionData['operator'], WorkflowCondition::getSupportedOperators())) {
            $errors[] = 'Invalid operator';
        }

        if (!isset($conditionData['value']) && 
            !in_array($conditionData['operator'], [
                WorkflowCondition::OPERATOR_IS_NULL,
                WorkflowCondition::OPERATOR_IS_NOT_NULL
            ])) {
            $errors[] = 'Value is required for this operator';
        }

        if ($conditionData['operator'] === WorkflowCondition::OPERATOR_BETWEEN) {
            if (!is_array($conditionData['value']) || count($conditionData['value']) !== 2) {
                $errors[] = 'Between operator requires exactly 2 values';
            }
        }

        return $errors;
    }

    // Comparison methods

    protected function equals($fieldValue, $compareValue): bool
    {
        if (is_array($compareValue) && count($compareValue) === 1) {
            $compareValue = $compareValue[0];
        }
        return $fieldValue == $compareValue;
    }

    protected function greaterThan($fieldValue, $compareValue): bool
    {
        if (is_array($compareValue)) {
            $compareValue = $compareValue[0] ?? 0;
        }
        return is_numeric($fieldValue) && is_numeric($compareValue) && $fieldValue > $compareValue;
    }

    protected function greaterThanOrEqual($fieldValue, $compareValue): bool
    {
        if (is_array($compareValue)) {
            $compareValue = $compareValue[0] ?? 0;
        }
        return is_numeric($fieldValue) && is_numeric($compareValue) && $fieldValue >= $compareValue;
    }

    protected function lessThan($fieldValue, $compareValue): bool
    {
        if (is_array($compareValue)) {
            $compareValue = $compareValue[0] ?? 0;
        }
        return is_numeric($fieldValue) && is_numeric($compareValue) && $fieldValue < $compareValue;
    }

    protected function lessThanOrEqual($fieldValue, $compareValue): bool
    {
        if (is_array($compareValue)) {
            $compareValue = $compareValue[0] ?? 0;
        }
        return is_numeric($fieldValue) && is_numeric($compareValue) && $fieldValue <= $compareValue;
    }

    protected function in($fieldValue, $compareValue): bool
    {
        if (!is_array($compareValue)) {
            $compareValue = [$compareValue];
        }
        return in_array($fieldValue, $compareValue, false);
    }

    protected function between($fieldValue, $compareValue): bool
    {
        if (!is_array($compareValue) || count($compareValue) !== 2) {
            return false;
        }
        
        return is_numeric($fieldValue) && 
               $fieldValue >= min($compareValue) && 
               $fieldValue <= max($compareValue);
    }

    protected function contains($fieldValue, $compareValue): bool
    {
        if (is_array($compareValue)) {
            $compareValue = $compareValue[0] ?? '';
        }
        
        if (is_array($fieldValue)) {
            return in_array($compareValue, $fieldValue, false);
        }
        
        return is_string($fieldValue) && str_contains($fieldValue, (string)$compareValue);
    }

    protected function startsWith($fieldValue, $compareValue): bool
    {
        if (is_array($compareValue)) {
            $compareValue = $compareValue[0] ?? '';
        }
        return is_string($fieldValue) && str_starts_with($fieldValue, (string)$compareValue);
    }

    protected function endsWith($fieldValue, $compareValue): bool
    {
        if (is_array($compareValue)) {
            $compareValue = $compareValue[0] ?? '';
        }
        return is_string($fieldValue) && str_ends_with($fieldValue, (string)$compareValue);
    }
}
