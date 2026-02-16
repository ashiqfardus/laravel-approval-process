<?php

namespace AshiqFardus\ApprovalProcess\Services;

use AshiqFardus\ApprovalProcess\Models\ApprovalRequest;
use AshiqFardus\ApprovalProcess\Models\ApprovalStep;
use AshiqFardus\ApprovalProcess\Models\ParallelStepGroup;
use AshiqFardus\ApprovalProcess\Models\ParallelExecutionState;
use AshiqFardus\ApprovalProcess\Models\ActiveParallelStep;
use Illuminate\Support\Facades\DB;

class ParallelWorkflowManager
{
    /**
     * Check if a step is a fork point (starts parallel execution).
     */
    public function isForkPoint(ApprovalStep $step): bool
    {
        return $step->execution_type === 'fork' || 
               ParallelStepGroup::where('fork_from_step_id', $step->id)->where('is_active', true)->exists();
    }

    /**
     * Check if a step is a join point (ends parallel execution).
     */
    public function isJoinPoint(ApprovalStep $step): bool
    {
        return $step->execution_type === 'join' ||
               ParallelStepGroup::where('join_to_step_id', $step->id)->where('is_active', true)->exists();
    }

    /**
     * Check if a step is part of a parallel group.
     */
    public function isParallelStep(ApprovalStep $step): bool
    {
        return $step->execution_type === 'parallel' && $step->parallel_group_id !== null;
    }

    /**
     * Fork workflow into parallel paths.
     */
    public function forkWorkflow(ApprovalRequest $request, ApprovalStep $forkStep): array
    {
        $parallelGroups = ParallelStepGroup::active()
            ->where('fork_from_step_id', $forkStep->id)
            ->with('steps')
            ->get();

        $activatedSteps = [];

        DB::transaction(function () use ($request, $parallelGroups, &$activatedSteps) {
            foreach ($parallelGroups as $group) {
                // Create execution state for this parallel group
                $executionState = ParallelExecutionState::create([
                    'approval_request_id' => $request->id,
                    'parallel_group_id' => $group->id,
                    'status' => ParallelExecutionState::STATUS_IN_PROGRESS,
                    'completed_steps' => 0,
                    'total_steps' => $group->steps->count(),
                    'step_statuses' => [],
                    'started_at' => now(),
                ]);

                // Activate all steps in the parallel group
                foreach ($group->steps as $step) {
                    $activeStep = ActiveParallelStep::create([
                        'approval_request_id' => $request->id,
                        'step_id' => $step->id,
                        'parallel_group_id' => $group->id,
                        'status' => ActiveParallelStep::STATUS_PENDING,
                        'activated_at' => now(),
                    ]);

                    $activatedSteps[] = $activeStep;
                }
            }
        });

        return $activatedSteps;
    }

    /**
     * Complete a parallel step and check if we can proceed to join.
     */
    public function completeParallelStep(
        ApprovalRequest $request,
        ApprovalStep $step,
        string $status = 'approved'
    ): ?ApprovalStep {
        return DB::transaction(function () use ($request, $step, $status) {
            // Mark the active parallel step as completed
            $activeStep = ActiveParallelStep::forRequest($request->id)
                ->where('step_id', $step->id)
                ->first();

            if (!$activeStep) {
                return null;
            }

            $activeStep->update([
                'status' => $status === 'approved' ? ActiveParallelStep::STATUS_APPROVED : ActiveParallelStep::STATUS_REJECTED,
                'completed_at' => now(),
            ]);

            // Update execution state
            $executionState = ParallelExecutionState::where('approval_request_id', $request->id)
                ->where('parallel_group_id', $step->parallel_group_id)
                ->first();

            if (!$executionState) {
                return null;
            }

            $executionState->markStepCompleted($step->id, $status);

            // Check if synchronization condition is met
            if ($executionState->isSyncConditionMet()) {
                $executionState->update([
                    'status' => ParallelExecutionState::STATUS_COMPLETED,
                    'completed_at' => now(),
                ]);

                // Get the join point step
                $parallelGroup = $executionState->parallelGroup;
                if ($parallelGroup->join_to_step_id) {
                    return ApprovalStep::find($parallelGroup->join_to_step_id);
                }
            }

            return null; // Still waiting for other parallel steps
        });
    }

    /**
     * Get all active parallel steps for a request.
     */
    public function getActiveParallelSteps(ApprovalRequest $request): array
    {
        return ActiveParallelStep::forRequest($request->id)
            ->with(['step', 'parallelGroup'])
            ->whereIn('status', [ActiveParallelStep::STATUS_PENDING, ActiveParallelStep::STATUS_IN_PROGRESS])
            ->get()
            ->toArray();
    }

    /**
     * Get parallel execution status for a request.
     */
    public function getExecutionStatus(ApprovalRequest $request): array
    {
        $states = ParallelExecutionState::where('approval_request_id', $request->id)
            ->with('parallelGroup')
            ->get();

        return $states->map(function ($state) {
            return [
                'group_id' => $state->parallel_group_id,
                'group_name' => $state->parallelGroup->name,
                'status' => $state->status,
                'completed_steps' => $state->completed_steps,
                'total_steps' => $state->total_steps,
                'completion_percentage' => $state->getCompletionPercentage(),
                'sync_type' => $state->parallelGroup->sync_type,
                'step_statuses' => $state->step_statuses,
                'started_at' => $state->started_at,
                'completed_at' => $state->completed_at,
            ];
        })->toArray();
    }

    /**
     * Check if a request is currently in parallel execution.
     */
    public function isInParallelExecution(ApprovalRequest $request): bool
    {
        return ParallelExecutionState::where('approval_request_id', $request->id)
            ->where('status', ParallelExecutionState::STATUS_IN_PROGRESS)
            ->exists();
    }

    /**
     * Get the next step after parallel execution completes.
     */
    public function getNextStepAfterJoin(ApprovalRequest $request, ParallelStepGroup $group): ?ApprovalStep
    {
        if ($group->join_to_step_id) {
            return ApprovalStep::find($group->join_to_step_id);
        }

        // If no explicit join step, get the next sequential step after the group
        $maxSequence = $group->steps()->max('sequence');
        return ApprovalStep::where('workflow_id', $request->workflow_id)
            ->where('sequence', '>', $maxSequence)
            ->orderBy('sequence')
            ->first();
    }

    /**
     * Cancel parallel execution for a request.
     */
    public function cancelParallelExecution(ApprovalRequest $request): void
    {
        DB::transaction(function () use ($request) {
            // Mark all execution states as failed
            ParallelExecutionState::where('approval_request_id', $request->id)
                ->where('status', ParallelExecutionState::STATUS_IN_PROGRESS)
                ->update([
                    'status' => ParallelExecutionState::STATUS_FAILED,
                    'completed_at' => now(),
                ]);

            // Remove active parallel steps
            ActiveParallelStep::forRequest($request->id)
                ->whereIn('status', [ActiveParallelStep::STATUS_PENDING, ActiveParallelStep::STATUS_IN_PROGRESS])
                ->delete();
        });
    }

    /**
     * Check if all required approvals in a parallel group are met.
     */
    public function areRequiredApprovalsMet(ApprovalRequest $request, ParallelStepGroup $group): bool
    {
        $executionState = ParallelExecutionState::where('approval_request_id', $request->id)
            ->where('parallel_group_id', $group->id)
            ->first();

        if (!$executionState) {
            return false;
        }

        return $executionState->isSyncConditionMet();
    }

    /**
     * Get pending approvers for parallel steps.
     */
    public function getPendingApprovers(ApprovalRequest $request): array
    {
        $activeSteps = ActiveParallelStep::forRequest($request->id)
            ->with(['step.approvers'])
            ->whereIn('status', [ActiveParallelStep::STATUS_PENDING, ActiveParallelStep::STATUS_IN_PROGRESS])
            ->get();

        $approvers = [];
        foreach ($activeSteps as $activeStep) {
            foreach ($activeStep->step->approvers as $approver) {
                $approvers[] = [
                    'step_id' => $activeStep->step_id,
                    'step_name' => $activeStep->step->name,
                    'user_id' => $approver->user_id,
                    'user' => $approver->user,
                ];
            }
        }

        return $approvers;
    }

    /**
     * Validate parallel group configuration.
     */
    public function validateParallelGroup(array $groupData): array
    {
        $errors = [];

        if (empty($groupData['name'])) {
            $errors[] = 'Group name is required';
        }

        if (empty($groupData['sync_type'])) {
            $errors[] = 'Sync type is required';
        } elseif (!in_array($groupData['sync_type'], ParallelStepGroup::getSyncTypes())) {
            $errors[] = 'Invalid sync type';
        }

        if ($groupData['sync_type'] === ParallelStepGroup::SYNC_CUSTOM && empty($groupData['required_approvals'])) {
            $errors[] = 'Required approvals must be specified for custom sync type';
        }

        return $errors;
    }

    /**
     * Create a parallel group with steps.
     */
    public function createParallelGroup(
        int $workflowId,
        string $name,
        array $stepIds,
        string $syncType = ParallelStepGroup::SYNC_ALL,
        ?int $forkFromStepId = null,
        ?int $joinToStepId = null,
        ?int $requiredApprovals = null
    ): ParallelStepGroup {
        return DB::transaction(function () use (
            $workflowId,
            $name,
            $stepIds,
            $syncType,
            $forkFromStepId,
            $joinToStepId,
            $requiredApprovals
        ) {
            $group = ParallelStepGroup::create([
                'workflow_id' => $workflowId,
                'name' => $name,
                'sync_type' => $syncType,
                'required_approvals' => $requiredApprovals,
                'fork_from_step_id' => $forkFromStepId,
                'join_to_step_id' => $joinToStepId,
                'is_active' => true,
            ]);

            // Update steps to belong to this parallel group
            foreach ($stepIds as $index => $stepId) {
                ApprovalStep::where('id', $stepId)->update([
                    'execution_type' => 'parallel',
                    'parallel_group_id' => $group->id,
                    'parallel_sequence' => $index + 1,
                ]);
            }

            return $group->fresh('steps');
        });
    }
}
