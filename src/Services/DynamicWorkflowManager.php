<?php

namespace AshiqFardus\ApprovalProcess\Services;

use AshiqFardus\ApprovalProcess\Models\ApprovalRequest;
use AshiqFardus\ApprovalProcess\Models\ApprovalStep;
use AshiqFardus\ApprovalProcess\Models\Workflow;
use AshiqFardus\ApprovalProcess\Models\WorkflowVersion;
use AshiqFardus\ApprovalProcess\Models\DynamicStepModification;
use AshiqFardus\ApprovalProcess\Models\DynamicApproverAssignment;
use AshiqFardus\ApprovalProcess\Models\WorkflowModificationRule;
use Illuminate\Support\Facades\DB;

class DynamicWorkflowManager
{
    /**
     * Add a new step to an active request's workflow.
     */
    public function addStepToRequest(
        ApprovalRequest $request,
        array $stepData,
        int $userId,
        ?string $reason = null
    ): ApprovalStep {
        return DB::transaction(function () use ($request, $stepData, $userId, $reason) {
            // Check if modification is allowed
            $this->validateModification($request->workflow_id, WorkflowModificationRule::RULE_ALLOW_STEP_ADDITION);

            // Create the new step
            $step = ApprovalStep::create(array_merge($stepData, [
                'workflow_id' => $request->workflow_id,
            ]));

            // Record the modification
            DynamicStepModification::create([
                'approval_request_id' => $request->id,
                'step_id' => $step->id,
                'modification_type' => DynamicStepModification::TYPE_ADDED,
                'new_data' => $step->toArray(),
                'reason' => $reason,
                'modified_by_user_id' => $userId,
                'is_applied' => true,
                'applied_at' => now(),
            ]);

            // Create workflow version snapshot
            WorkflowVersion::createSnapshot(
                $request->workflow,
                WorkflowVersion::CHANGE_TYPE_STEP_ADDED,
                $userId,
                "Step '{$step->name}' added to request #{$request->id}"
            );

            return $step;
        });
    }

    /**
     * Remove a step from an active request's workflow.
     */
    public function removeStepFromRequest(
        ApprovalRequest $request,
        int $stepId,
        int $userId,
        ?string $reason = null
    ): bool {
        return DB::transaction(function () use ($request, $stepId, $userId, $reason) {
            // Check if modification is allowed
            $this->validateModification($request->workflow_id, WorkflowModificationRule::RULE_ALLOW_STEP_REMOVAL);

            $step = ApprovalStep::findOrFail($stepId);

            // Don't allow removing the current step
            if ($request->current_step_id === $stepId) {
                throw new \Exception('Cannot remove the current step');
            }

            // Record the modification before deleting
            DynamicStepModification::create([
                'approval_request_id' => $request->id,
                'step_id' => $stepId,
                'modification_type' => DynamicStepModification::TYPE_REMOVED,
                'old_data' => $step->toArray(),
                'reason' => $reason,
                'modified_by_user_id' => $userId,
                'is_applied' => true,
                'applied_at' => now(),
            ]);

            // Create workflow version snapshot
            WorkflowVersion::createSnapshot(
                $request->workflow,
                WorkflowVersion::CHANGE_TYPE_STEP_REMOVED,
                $userId,
                "Step '{$step->name}' removed from request #{$request->id}"
            );

            // Soft delete or mark as inactive
            $step->update(['is_active' => false]);

            return true;
        });
    }

    /**
     * Skip a step in an active request.
     */
    public function skipStep(
        ApprovalRequest $request,
        int $stepId,
        int $userId,
        string $reason
    ): bool {
        return DB::transaction(function () use ($request, $stepId, $userId, $reason) {
            // Check if modification is allowed
            $this->validateModification($request->workflow_id, WorkflowModificationRule::RULE_ALLOW_SKIP_STEP);

            $step = ApprovalStep::findOrFail($stepId);

            // Record the modification
            DynamicStepModification::create([
                'approval_request_id' => $request->id,
                'step_id' => $stepId,
                'modification_type' => DynamicStepModification::TYPE_SKIPPED,
                'old_data' => ['status' => 'pending'],
                'new_data' => ['status' => 'skipped'],
                'reason' => $reason,
                'modified_by_user_id' => $userId,
                'is_applied' => true,
                'applied_at' => now(),
            ]);

            // If this is the current step, move to next
            if ($request->current_step_id === $stepId) {
                $nextStep = $step->getNextStep();
                if ($nextStep) {
                    $request->update(['current_step_id' => $nextStep->id]);
                }
            }

            return true;
        });
    }

    /**
     * Assign a dynamic approver to a step for a specific request.
     */
    public function assignDynamicApprover(
        ApprovalRequest $request,
        int $stepId,
        int $newApproverId,
        int $userId,
        string $assignmentType = DynamicApproverAssignment::TYPE_ADDITION,
        ?int $originalApproverId = null,
        ?string $reason = null,
        ?\DateTime $validUntil = null
    ): DynamicApproverAssignment {
        return DB::transaction(function () use (
            $request,
            $stepId,
            $newApproverId,
            $userId,
            $assignmentType,
            $originalApproverId,
            $reason,
            $validUntil
        ) {
            // Check if modification is allowed
            $this->validateModification($request->workflow_id, WorkflowModificationRule::RULE_ALLOW_APPROVER_CHANGE);

            $assignment = DynamicApproverAssignment::create([
                'approval_request_id' => $request->id,
                'step_id' => $stepId,
                'original_approver_id' => $originalApproverId,
                'new_approver_id' => $newApproverId,
                'assignment_type' => $assignmentType,
                'reason' => $reason,
                'assigned_by_user_id' => $userId,
                'valid_from' => now(),
                'valid_until' => $validUntil,
                'is_active' => true,
            ]);

            // Create workflow version snapshot
            WorkflowVersion::createSnapshot(
                $request->workflow,
                WorkflowVersion::CHANGE_TYPE_APPROVER_CHANGED,
                $userId,
                "Approver changed for step #{$stepId} in request #{$request->id}"
            );

            return $assignment;
        });
    }

    /**
     * Get all dynamic modifications for a request.
     */
    public function getRequestModifications(ApprovalRequest $request): array
    {
        $modifications = DynamicStepModification::forRequest($request->id)
            ->with(['step', 'modifiedBy'])
            ->orderBy('created_at', 'desc')
            ->get();

        $approverAssignments = DynamicApproverAssignment::forRequest($request->id)
            ->with(['step', 'originalApprover', 'newApprover', 'assignedBy'])
            ->orderBy('created_at', 'desc')
            ->get();

        return [
            'step_modifications' => $modifications->toArray(),
            'approver_assignments' => $approverAssignments->toArray(),
        ];
    }

    /**
     * Get workflow version history.
     */
    public function getVersionHistory(int $workflowId): array
    {
        return WorkflowVersion::forWorkflow($workflowId)
            ->with('changedBy')
            ->orderBy('version_number', 'desc')
            ->get()
            ->map(function ($version) {
                return [
                    'id' => $version->id,
                    'version_number' => $version->version_number,
                    'change_type' => $version->change_type,
                    'change_description' => $version->change_description,
                    'changed_by' => $version->changedBy ? [
                        'id' => $version->changedBy->id,
                        'name' => $version->changedBy->name,
                    ] : null,
                    'is_active' => $version->is_active,
                    'created_at' => $version->created_at->toIso8601String(),
                ];
            })
            ->toArray();
    }

    /**
     * Rollback to a previous workflow version.
     */
    public function rollbackToVersion(
        int $workflowId,
        int $versionNumber,
        int $userId,
        ?string $reason = null
    ): Workflow {
        return DB::transaction(function () use ($workflowId, $versionNumber, $userId, $reason) {
            $version = WorkflowVersion::forWorkflow($workflowId)
                ->where('version_number', $versionNumber)
                ->firstOrFail();

            $workflow = Workflow::findOrFail($workflowId);

            // Restore workflow state
            $workflow->update($version->workflow_snapshot);

            // Restore steps (simplified - in production you'd want more sophisticated logic)
            // This is a basic implementation
            $workflow->steps()->delete();
            foreach ($version->steps_snapshot as $stepData) {
                ApprovalStep::create(array_merge($stepData, [
                    'workflow_id' => $workflow->id,
                ]));
            }

            // Create new version for the rollback
            WorkflowVersion::createSnapshot(
                $workflow,
                'rollback',
                $userId,
                "Rolled back to version {$versionNumber}. Reason: {$reason}"
            );

            return $workflow->fresh('steps');
        });
    }

    /**
     * Get dynamic approvers for a step in a specific request.
     */
    public function getDynamicApprovers(ApprovalRequest $request, int $stepId): array
    {
        return DynamicApproverAssignment::forRequest($request->id)
            ->where('step_id', $stepId)
            ->active()
            ->with(['newApprover', 'originalApprover'])
            ->get()
            ->toArray();
    }

    /**
     * Check if a modification is allowed by workflow rules.
     */
    protected function validateModification(int $workflowId, string $ruleType): void
    {
        $rule = WorkflowModificationRule::forWorkflow($workflowId)
            ->byType($ruleType)
            ->active()
            ->first();

        if (!$rule) {
            throw new \Exception("Modification type '{$ruleType}' is not allowed for this workflow");
        }

        // Additional validation based on rule conditions could be added here
    }

    /**
     * Check if a workflow allows dynamic modifications.
     */
    public function allowsModifications(int $workflowId): bool
    {
        return WorkflowModificationRule::forWorkflow($workflowId)
            ->active()
            ->exists();
    }

    /**
     * Get modification statistics for a workflow.
     */
    public function getModificationStats(int $workflowId): array
    {
        $workflow = Workflow::findOrFail($workflowId);

        $totalModifications = DynamicStepModification::whereHas('approvalRequest', function ($q) use ($workflowId) {
            $q->where('workflow_id', $workflowId);
        })->count();

        $modificationsByType = DynamicStepModification::whereHas('approvalRequest', function ($q) use ($workflowId) {
            $q->where('workflow_id', $workflowId);
        })
        ->select('modification_type', DB::raw('count(*) as count'))
        ->groupBy('modification_type')
        ->get()
        ->pluck('count', 'modification_type')
        ->toArray();

        $totalVersions = WorkflowVersion::forWorkflow($workflowId)->count();

        return [
            'workflow_id' => $workflowId,
            'workflow_name' => $workflow->name,
            'total_modifications' => $totalModifications,
            'modifications_by_type' => $modificationsByType,
            'total_versions' => $totalVersions,
            'current_version' => WorkflowVersion::getLatestVersionNumber($workflowId),
            'allows_modifications' => $this->allowsModifications($workflowId),
        ];
    }

    /**
     * Validate modification rule configuration.
     */
    public function validateModificationRule(array $ruleData): array
    {
        $errors = [];

        if (empty($ruleData['rule_type'])) {
            $errors[] = 'Rule type is required';
        } elseif (!in_array($ruleData['rule_type'], WorkflowModificationRule::getRuleTypes())) {
            $errors[] = 'Invalid rule type';
        }

        if (isset($ruleData['requires_approval']) && $ruleData['requires_approval'] && empty($ruleData['approval_required_from_user_id'])) {
            $errors[] = 'Approval user is required when requires_approval is true';
        }

        return $errors;
    }
}
