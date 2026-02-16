<?php

namespace AshiqFardus\ApprovalProcess\Http\Controllers;

use AshiqFardus\ApprovalProcess\Models\ApprovalRequest;
use AshiqFardus\ApprovalProcess\Models\Workflow;
use AshiqFardus\ApprovalProcess\Models\WorkflowModificationRule;
use AshiqFardus\ApprovalProcess\Services\DynamicWorkflowManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DynamicWorkflowController extends Controller
{
    protected DynamicWorkflowManager $manager;

    public function __construct(DynamicWorkflowManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * Add a step to an active request's workflow.
     */
    public function addStepToRequest($request_id): JsonResponse
    {
        $request = ApprovalRequest::findOrFail($request_id);
        $httpRequest = request();

        try {
            $step = $this->manager->addStepToRequest(
                $request,
                $httpRequest->input('step_data', []),
                auth()->id() ?? $request->requested_by_user_id,
                $httpRequest->input('reason')
            );

            return response()->json([
                'message' => 'Step added successfully',
                'step' => $step,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Remove a step from an active request's workflow.
     */
    public function removeStepFromRequest($request_id, $step_id): JsonResponse
    {
        $request = ApprovalRequest::findOrFail($request_id);
        $httpRequest = request();

        try {
            $this->manager->removeStepFromRequest(
                $request,
                $step_id,
                auth()->id() ?? $request->requested_by_user_id,
                $httpRequest->input('reason')
            );

            return response()->json([
                'message' => 'Step removed successfully',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Skip a step in an active request.
     */
    public function skipStep($request_id, $step_id): JsonResponse
    {
        $request = ApprovalRequest::findOrFail($request_id);
        $httpRequest = request();

        try {
            $this->manager->skipStep(
                $request,
                $step_id,
                auth()->id() ?? $request->requested_by_user_id,
                $httpRequest->input('reason', 'Step skipped by administrator')
            );

            return response()->json([
                'message' => 'Step skipped successfully',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Assign a dynamic approver.
     */
    public function assignApprover($request_id, $step_id): JsonResponse
    {
        $request = ApprovalRequest::findOrFail($request_id);
        $httpRequest = request();

        try {
            $assignment = $this->manager->assignDynamicApprover(
                $request,
                $step_id,
                $httpRequest->input('new_approver_id'),
                auth()->id() ?? $request->requested_by_user_id,
                $httpRequest->input('assignment_type', 'addition'),
                $httpRequest->input('original_approver_id'),
                $httpRequest->input('reason'),
                $httpRequest->input('valid_until') ? new \DateTime($httpRequest->input('valid_until')) : null
            );

            return response()->json([
                'message' => 'Approver assigned successfully',
                'assignment' => $assignment,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Get all modifications for a request.
     */
    public function requestModifications($request_id): JsonResponse
    {
        $request = ApprovalRequest::findOrFail($request_id);
        
        $modifications = $this->manager->getRequestModifications($request);

        return response()->json($modifications);
    }

    /**
     * Get dynamic approvers for a step.
     */
    public function stepDynamicApprovers($request_id, $step_id): JsonResponse
    {
        $request = ApprovalRequest::findOrFail($request_id);
        
        $approvers = $this->manager->getDynamicApprovers($request, $step_id);

        return response()->json([
            'request_id' => $request->id,
            'step_id' => $step_id,
            'dynamic_approvers' => $approvers,
        ]);
    }

    // Workflow Versioning

    /**
     * Get version history for a workflow.
     */
    public function versionHistory($workflow_id): JsonResponse
    {
        $history = $this->manager->getVersionHistory($workflow_id);

        return response()->json([
            'workflow_id' => $workflow_id,
            'versions' => $history,
        ]);
    }

    /**
     * Rollback to a previous version.
     */
    public function rollbackVersion($workflow_id, $version_number): JsonResponse
    {
        $httpRequest = request();

        try {
            $workflow = $this->manager->rollbackToVersion(
                $workflow_id,
                $version_number,
                auth()->id() ?? 1,
                $httpRequest->input('reason', 'Rollback requested')
            );

            return response()->json([
                'message' => 'Workflow rolled back successfully',
                'workflow' => $workflow,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 422);
        }
    }

    // Modification Rules

    /**
     * Get modification rules for a workflow.
     */
    public function modificationRules($workflow_id): JsonResponse
    {
        $rules = WorkflowModificationRule::forWorkflow($workflow_id)
            ->with('approvalRequiredFrom')
            ->get();

        return response()->json($rules);
    }

    /**
     * Create a modification rule.
     */
    public function createModificationRule($workflow_id): JsonResponse
    {
        $workflow = Workflow::findOrFail($workflow_id);
        $httpRequest = request();

        // Validate
        $errors = $this->manager->validateModificationRule($httpRequest->all());
        if (!empty($errors)) {
            return response()->json(['errors' => $errors], 422);
        }

        $rule = WorkflowModificationRule::create([
            'workflow_id' => $workflow_id,
            'rule_type' => $httpRequest->input('rule_type'),
            'conditions' => $httpRequest->input('conditions', []),
            'restrictions' => $httpRequest->input('restrictions', []),
            'requires_approval' => $httpRequest->input('requires_approval', false),
            'approval_required_from_user_id' => $httpRequest->input('approval_required_from_user_id'),
            'is_active' => $httpRequest->input('is_active', true),
        ]);

        return response()->json($rule, 201);
    }

    /**
     * Update a modification rule.
     */
    public function updateModificationRule($workflow_id, $rule_id): JsonResponse
    {
        $rule = WorkflowModificationRule::forWorkflow($workflow_id)->findOrFail($rule_id);
        $httpRequest = request();

        $rule->update($httpRequest->only([
            'rule_type',
            'conditions',
            'restrictions',
            'requires_approval',
            'approval_required_from_user_id',
            'is_active',
        ]));

        return response()->json($rule->fresh('approvalRequiredFrom'));
    }

    /**
     * Delete a modification rule.
     */
    public function deleteModificationRule($workflow_id, $rule_id): JsonResponse
    {
        $rule = WorkflowModificationRule::forWorkflow($workflow_id)->findOrFail($rule_id);
        $rule->delete();

        return response()->json(null, 204);
    }

    /**
     * Get modification statistics.
     */
    public function modificationStats($workflow_id): JsonResponse
    {
        $stats = $this->manager->getModificationStats($workflow_id);

        return response()->json($stats);
    }
}
