<?php

namespace AshiqFardus\ApprovalProcess\Http\Controllers;

use AshiqFardus\ApprovalProcess\Models\WorkflowCondition;
use AshiqFardus\ApprovalProcess\Models\WorkflowConditionGroup;
use AshiqFardus\ApprovalProcess\Models\Workflow;
use AshiqFardus\ApprovalProcess\Services\ConditionEvaluator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkflowConditionController extends Controller
{
    protected ConditionEvaluator $evaluator;

    public function __construct(ConditionEvaluator $evaluator)
    {
        $this->evaluator = $evaluator;
    }

    /**
     * Get all conditions for a workflow.
     */
    public function index($workflow_id): JsonResponse
    {
        $workflow = Workflow::findOrFail($workflow_id);
        
        $conditions = WorkflowCondition::forWorkflow($workflow_id)
            ->with(['fromStep', 'toStep', 'groups'])
            ->orderBy('priority', 'desc')
            ->get();

        return response()->json($conditions);
    }

    /**
     * Create a new workflow condition.
     */
    public function store($workflow_id): JsonResponse
    {
        $workflow = Workflow::findOrFail($workflow_id);
        $request = request();

        // Validate condition
        $errors = $this->evaluator->validateCondition($request->all());
        if (!empty($errors)) {
            return response()->json(['errors' => $errors], 422);
        }

        $condition = WorkflowCondition::create([
            'workflow_id' => $workflow_id,
            'from_step_id' => $request->input('from_step_id'),
            'to_step_id' => $request->input('to_step_id'),
            'name' => $request->input('name'),
            'field' => $request->input('field'),
            'operator' => $request->input('operator'),
            'value' => $request->input('value'),
            'logic_operator' => $request->input('logic_operator', 'and'),
            'priority' => $request->input('priority', 0),
            'is_active' => $request->input('is_active', true),
        ]);

        return response()->json($condition->load(['fromStep', 'toStep']), 201);
    }

    /**
     * Get a specific condition.
     */
    public function show($workflow_id, $condition_id): JsonResponse
    {
        $condition = WorkflowCondition::forWorkflow($workflow_id)
            ->with(['fromStep', 'toStep', 'groups'])
            ->findOrFail($condition_id);

        return response()->json($condition);
    }

    /**
     * Update a condition.
     */
    public function update($workflow_id, $condition_id): JsonResponse
    {
        $condition = WorkflowCondition::forWorkflow($workflow_id)->findOrFail($condition_id);
        $request = request();

        // Validate condition
        $errors = $this->evaluator->validateCondition($request->all());
        if (!empty($errors)) {
            return response()->json(['errors' => $errors], 422);
        }

        $condition->update($request->only([
            'from_step_id',
            'to_step_id',
            'name',
            'field',
            'operator',
            'value',
            'logic_operator',
            'priority',
            'is_active',
        ]));

        return response()->json($condition->fresh(['fromStep', 'toStep']));
    }

    /**
     * Delete a condition.
     */
    public function destroy($workflow_id, $condition_id): JsonResponse
    {
        $condition = WorkflowCondition::forWorkflow($workflow_id)->findOrFail($condition_id);
        $condition->delete();

        return response()->json(null, 204);
    }

    /**
     * Test a condition against sample data.
     */
    public function test($workflow_id, $condition_id): JsonResponse
    {
        $condition = WorkflowCondition::forWorkflow($workflow_id)->findOrFail($condition_id);
        $request = request();
        
        $testData = $request->input('data', []);
        $result = $this->evaluator->evaluateCondition($condition, $testData);

        return response()->json([
            'condition' => $condition,
            'test_data' => $testData,
            'result' => $result,
            'message' => $result ? 'Condition passed' : 'Condition failed',
        ]);
    }

    /**
     * Get possible next steps from a given step.
     */
    public function possibleNextSteps($workflow_id, $step_id): JsonResponse
    {
        $workflow = Workflow::findOrFail($workflow_id);
        
        $possibleSteps = $this->evaluator->getPossibleNextSteps($workflow_id, $step_id);

        return response()->json([
            'workflow_id' => $workflow_id,
            'current_step_id' => $step_id,
            'possible_next_steps' => $possibleSteps,
        ]);
    }

    /**
     * Get supported operators.
     */
    public function operators(): JsonResponse
    {
        return response()->json([
            'operators' => WorkflowCondition::getSupportedOperators(),
            'descriptions' => [
                '=' => 'Equals',
                '!=' => 'Not equals',
                '>' => 'Greater than',
                '>=' => 'Greater than or equal',
                '<' => 'Less than',
                '<=' => 'Less than or equal',
                'in' => 'In array',
                'not_in' => 'Not in array',
                'between' => 'Between two values',
                'contains' => 'Contains substring',
                'not_contains' => 'Does not contain substring',
                'starts_with' => 'Starts with',
                'ends_with' => 'Ends with',
                'is_null' => 'Is null',
                'is_not_null' => 'Is not null',
            ],
        ]);
    }

    // Condition Groups

    /**
     * Get all condition groups for a workflow.
     */
    public function indexGroups($workflow_id): JsonResponse
    {
        $groups = WorkflowConditionGroup::forWorkflow($workflow_id)
            ->with('conditions')
            ->orderBy('priority', 'desc')
            ->get();

        return response()->json($groups);
    }

    /**
     * Create a new condition group.
     */
    public function storeGroup($workflow_id): JsonResponse
    {
        $workflow = Workflow::findOrFail($workflow_id);
        $request = request();

        $group = WorkflowConditionGroup::create([
            'workflow_id' => $workflow_id,
            'name' => $request->input('name'),
            'logic_operator' => $request->input('logic_operator', 'and'),
            'priority' => $request->input('priority', 0),
            'is_active' => $request->input('is_active', true),
        ]);

        // Attach conditions if provided
        if ($request->has('condition_ids')) {
            $conditionIds = $request->input('condition_ids');
            foreach ($conditionIds as $index => $conditionId) {
                $group->conditions()->attach($conditionId, ['sequence' => $index]);
            }
        }

        return response()->json($group->load('conditions'), 201);
    }

    /**
     * Update a condition group.
     */
    public function updateGroup($workflow_id, $group_id): JsonResponse
    {
        $group = WorkflowConditionGroup::forWorkflow($workflow_id)->findOrFail($group_id);
        $request = request();

        $group->update($request->only([
            'name',
            'logic_operator',
            'priority',
            'is_active',
        ]));

        // Update conditions if provided
        if ($request->has('condition_ids')) {
            $group->conditions()->detach();
            $conditionIds = $request->input('condition_ids');
            foreach ($conditionIds as $index => $conditionId) {
                $group->conditions()->attach($conditionId, ['sequence' => $index]);
            }
        }

        return response()->json($group->fresh('conditions'));
    }

    /**
     * Delete a condition group.
     */
    public function destroyGroup($workflow_id, $group_id): JsonResponse
    {
        $group = WorkflowConditionGroup::forWorkflow($workflow_id)->findOrFail($group_id);
        $group->delete();

        return response()->json(null, 204);
    }

    /**
     * Test a condition group against sample data.
     */
    public function testGroup($workflow_id, $group_id): JsonResponse
    {
        $group = WorkflowConditionGroup::forWorkflow($workflow_id)
            ->with('conditions')
            ->findOrFail($group_id);
        
        $request = request();
        $testData = $request->input('data', []);
        
        $result = $this->evaluator->evaluateGroup($group, $testData);

        return response()->json([
            'group' => $group,
            'test_data' => $testData,
            'result' => $result,
            'message' => $result ? 'Group conditions passed' : 'Group conditions failed',
        ]);
    }
}
