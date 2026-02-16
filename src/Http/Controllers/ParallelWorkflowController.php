<?php

namespace AshiqFardus\ApprovalProcess\Http\Controllers;

use AshiqFardus\ApprovalProcess\Models\Workflow;
use AshiqFardus\ApprovalProcess\Models\ApprovalRequest;
use AshiqFardus\ApprovalProcess\Models\ParallelStepGroup;
use AshiqFardus\ApprovalProcess\Services\ParallelWorkflowManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ParallelWorkflowController extends Controller
{
    protected ParallelWorkflowManager $manager;

    public function __construct(ParallelWorkflowManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * Get all parallel groups for a workflow.
     */
    public function index($workflow_id): JsonResponse
    {
        $workflow = Workflow::findOrFail($workflow_id);
        
        $groups = ParallelStepGroup::forWorkflow($workflow_id)
            ->with(['steps', 'forkFromStep', 'joinToStep'])
            ->get();

        return response()->json($groups);
    }

    /**
     * Create a new parallel group.
     */
    public function store($workflow_id): JsonResponse
    {
        $workflow = Workflow::findOrFail($workflow_id);
        $request = request();

        // Validate
        $errors = $this->manager->validateParallelGroup($request->all());
        if (!empty($errors)) {
            return response()->json(['errors' => $errors], 422);
        }

        $group = $this->manager->createParallelGroup(
            $workflow_id,
            $request->input('name'),
            $request->input('step_ids', []),
            $request->input('sync_type', ParallelStepGroup::SYNC_ALL),
            $request->input('fork_from_step_id'),
            $request->input('join_to_step_id'),
            $request->input('required_approvals')
        );

        return response()->json($group, 201);
    }

    /**
     * Get a specific parallel group.
     */
    public function show($workflow_id, $group_id): JsonResponse
    {
        $group = ParallelStepGroup::forWorkflow($workflow_id)
            ->with(['steps', 'forkFromStep', 'joinToStep'])
            ->findOrFail($group_id);

        return response()->json($group);
    }

    /**
     * Update a parallel group.
     */
    public function update($workflow_id, $group_id): JsonResponse
    {
        $group = ParallelStepGroup::forWorkflow($workflow_id)->findOrFail($group_id);
        $request = request();

        // Validate
        $errors = $this->manager->validateParallelGroup($request->all());
        if (!empty($errors)) {
            return response()->json(['errors' => $errors], 422);
        }

        $group->update($request->only([
            'name',
            'sync_type',
            'required_approvals',
            'fork_from_step_id',
            'join_to_step_id',
            'is_active',
        ]));

        return response()->json($group->fresh(['steps', 'forkFromStep', 'joinToStep']));
    }

    /**
     * Delete a parallel group.
     */
    public function destroy($workflow_id, $group_id): JsonResponse
    {
        $group = ParallelStepGroup::forWorkflow($workflow_id)->findOrFail($group_id);
        
        // Check if group is being used in active requests
        if ($group->executionStates()->whereIn('status', ['pending', 'in_progress'])->exists()) {
            return response()->json([
                'message' => 'Cannot delete parallel group with active executions'
            ], 422);
        }

        $group->delete();

        return response()->json(null, 204);
    }

    /**
     * Get parallel execution status for a request.
     */
    public function requestStatus($request_id): JsonResponse
    {
        $request = ApprovalRequest::findOrFail($request_id);
        
        $status = $this->manager->getExecutionStatus($request);
        $activeSteps = $this->manager->getActiveParallelSteps($request);
        $isInParallel = $this->manager->isInParallelExecution($request);

        return response()->json([
            'request_id' => $request->id,
            'is_in_parallel_execution' => $isInParallel,
            'parallel_groups' => $status,
            'active_steps' => $activeSteps,
        ]);
    }

    /**
     * Get pending approvers for parallel steps.
     */
    public function pendingApprovers($request_id): JsonResponse
    {
        $request = ApprovalRequest::findOrFail($request_id);
        
        $approvers = $this->manager->getPendingApprovers($request);

        return response()->json([
            'request_id' => $request->id,
            'pending_approvers' => $approvers,
        ]);
    }

    /**
     * Get supported sync types.
     */
    public function syncTypes(): JsonResponse
    {
        return response()->json([
            'sync_types' => ParallelStepGroup::getSyncTypes(),
            'descriptions' => [
                'all' => 'Wait for all parallel steps to complete',
                'any' => 'Continue when any step completes',
                'majority' => 'Continue when majority of steps complete',
                'custom' => 'Continue when specified number of steps complete',
            ],
        ]);
    }

    /**
     * Simulate parallel execution.
     */
    public function simulate($workflow_id, $group_id): JsonResponse
    {
        $group = ParallelStepGroup::forWorkflow($workflow_id)
            ->with('steps')
            ->findOrFail($group_id);

        $request = request();
        $completedSteps = $request->input('completed_steps', 0);
        $totalSteps = $group->steps->count();

        $isSyncMet = $group->isSyncConditionMet($completedSteps, $totalSteps);

        return response()->json([
            'group' => $group,
            'total_steps' => $totalSteps,
            'completed_steps' => $completedSteps,
            'sync_type' => $group->sync_type,
            'required_approvals' => $group->required_approvals,
            'sync_condition_met' => $isSyncMet,
            'completion_percentage' => $totalSteps > 0 ? round(($completedSteps / $totalSteps) * 100, 2) : 0,
        ]);
    }
}
