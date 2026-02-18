<?php

namespace AshiqFardus\ApprovalProcess\Services;

use AshiqFardus\ApprovalProcess\Models\ApprovalRequest;
use AshiqFardus\ApprovalProcess\Models\ApprovalStep;
use AshiqFardus\ApprovalProcess\Models\ApprovalAction;
use AshiqFardus\ApprovalProcess\Models\Workflow;
use Illuminate\Database\Eloquent\Model;

class ApprovalEngine
{
    protected ConditionEvaluator $conditionEvaluator;
    protected ParallelWorkflowManager $parallelManager;
    protected WeightageCalculator $weightageCalculator;

    public function __construct(
        ConditionEvaluator $conditionEvaluator = null,
        ParallelWorkflowManager $parallelManager = null,
        WeightageCalculator $weightageCalculator = null
    ) {
        $this->conditionEvaluator = $conditionEvaluator ?? new ConditionEvaluator();
        $this->parallelManager = $parallelManager ?? new ParallelWorkflowManager();
        $this->weightageCalculator = $weightageCalculator ?? new WeightageCalculator();
    }
    /**
     * Submit a request for approval.
     * 
     * @param Model|object $model Eloquent model or any object with id property
     * @param int $userId
     * @param array $metadata
     * @return ApprovalRequest
     */
    public function submitRequest($model, int $userId, array $metadata = []): ApprovalRequest
    {
        $modelClass = get_class($model);

        // Support query-based workflows (e.g., 'query:sql', 'query:view')
        if (isset($metadata['query_based']) && $metadata['query_based']) {
            $queryType = $model->type ?? 'unknown';
            $workflowType = "query:{$queryType}";
            
            $workflow = Workflow::where('model_type', $workflowType)
                ->where('is_active', true)
                ->first();
        } else {
            $workflow = Workflow::where('model_type', $modelClass)
                ->where('is_active', true)
                ->first();
        }

        if (!$workflow) {
            throw new \Exception("No active workflow found for {$modelClass}");
        }

        $request = ApprovalRequest::create([
            'workflow_id' => $workflow->id,
            'requestable_type' => $modelClass,
            'requestable_id' => $model->id ?? $metadata['requestable_id'] ?? null,
            'requested_by_user_id' => $userId,
            'status' => ApprovalRequest::STATUS_SUBMITTED,
            'data_snapshot' => method_exists($model, 'toArray') ? $model->toArray() : json_decode(json_encode($model), true),
            'metadata' => $metadata,
            'submitted_at' => now(),
        ]);

        // Set initial step
        $firstStep = $workflow->activeSteps()->first();
        if ($firstStep) {
            $request->update(['current_step_id' => $firstStep->id]);
        }

        return $request;
    }

    /**
     * Approve a request at current step.
     */
    public function approve(ApprovalRequest $request, int $userId, ?string $remarks = null): bool
    {
        $step = $request->currentStep;

        if (!$step) {
            throw new \Exception("No current step found for request #{$request->id}");
        }

        // Mark the approver as approved
        $approver = $step->approvers()->where('user_id', $userId)->first();
        if ($approver) {
            $approver->update([
                'is_approved' => true,
                'approval_at' => now(),
            ]);
        }

        // Record the action
        ApprovalAction::recordAction(
            $request,
            $step,
            $userId,
            ApprovalAction::ACTION_APPROVED,
            $remarks
        );

        // Update current approval percentage
        $currentPercentage = $this->weightageCalculator->calculateCurrentPercentage($step);
        $request->update(['current_approval_percentage' => $currentPercentage]);

        // Check if this step requires all approvers to approve
        if ($step->isSerial() || $step->isParallel()) {
            $this->checkStepCompletion($request, $step);
        } elseif ($step->isAnyOne()) {
            // Any one can approve, so move to next step
            $this->moveToNextStep($request, $step);
        }

        return true;
    }

    /**
     * Reject a request at current step.
     */
    public function reject(
        ApprovalRequest $request,
        int $userId,
        string $reason,
        ?string $remarks = null
    ): bool {
        $step = $request->currentStep;

        if (!$step) {
            throw new \Exception("No current step found for request #{$request->id}");
        }

        // Record the action
        ApprovalAction::recordAction(
            $request,
            $step,
            $userId,
            ApprovalAction::ACTION_REJECTED,
            $remarks,
            ['rejection_reason' => $reason]
        );

        // Update request status
        $request->update([
            'status' => ApprovalRequest::STATUS_REJECTED,
            'rejection_reason' => $reason,
            'rejected_at' => now(),
        ]);

        return true;
    }

    /**
     * Send back a request to previous step.
     */
    public function sendBack(ApprovalRequest $request, int $userId, ?string $remarks = null): bool
    {
        $step = $request->currentStep;
        $previousStep = $step->getPreviousStep();

        if (!$previousStep) {
            throw new \Exception("No previous step to send back to");
        }

        ApprovalAction::recordAction(
            $request,
            $step,
            $userId,
            ApprovalAction::ACTION_SENT_BACK,
            $remarks
        );

        $request->update(['current_step_id' => $previousStep->id]);

        return true;
    }

    /**
     * Hold a request.
     */
    public function hold(ApprovalRequest $request, int $userId, ?string $remarks = null): bool
    {
        $step = $request->currentStep;

        ApprovalAction::recordAction(
            $request,
            $step,
            $userId,
            ApprovalAction::ACTION_HELD,
            $remarks
        );

        $request->update(['status' => ApprovalRequest::STATUS_PENDING]);

        return true;
    }

    /**
     * Check if current step is complete.
     */
    protected function checkStepCompletion(ApprovalRequest $request, ApprovalStep $step): void
    {
        // Use weightage-based calculation
        $hasReachedMinimum = $this->weightageCalculator->hasReachedMinimumPercentage($step);

        if ($hasReachedMinimum) {
            $this->moveToNextStep($request, $step);
        }
    }

    /**
     * Move to next step in workflow.
     */
    protected function moveToNextStep(ApprovalRequest $request, ApprovalStep $currentStep): void
    {
        // Check if this is a fork point (start of parallel execution)
        if ($this->parallelManager->isForkPoint($currentStep)) {
            $this->parallelManager->forkWorkflow($request, $currentStep);
            // Don't update current_step_id - request is now in parallel execution
            return;
        }

        // Check for conditional routing
        $requestData = $request->data_snapshot ?? [];
        $conditionalNextStepId = $this->conditionEvaluator->findNextStep(
            $request->workflow_id,
            $currentStep->id,
            $requestData
        );

        if ($conditionalNextStepId) {
            // Conditional route found
            $nextStep = ApprovalStep::find($conditionalNextStepId);
            if ($nextStep) {
                $request->update(['current_step_id' => $nextStep->id]);
                return;
            }
        }

        // Fall back to default sequential routing
        $nextStep = $currentStep->getNextStep();
        
        if ($nextStep) {
            $request->update(['current_step_id' => $nextStep->id]);
        } else {
            // No next step - workflow is complete
            $request->update([
                'current_step_id' => null,
                'status' => ApprovalRequest::STATUS_APPROVED,
                'completed_at' => now(),
            ]);
        }
    }

    /**
     * Submit request with auto-approval for higher-level creators.
     *
     * @param Model|object $model
     * @param int $userId
     * @param array $metadata
     * @return ApprovalRequest
     */
    public function createWithAutoApproval($model, int $userId, array $metadata = []): ApprovalRequest
    {
        $modelClass = get_class($model);
        $permissionService = app(ApprovalPermissionService::class);
        
        // Detect creator's level
        $creatorLevel = $permissionService->getUserLevel($userId, $modelClass);
        
        // Create the request
        $request = $this->submitRequest($model, $userId, $metadata);
        
        // If creator has approval level, auto-approve previous levels
        if ($creatorLevel) {
            $request->update([
                'creator_level' => $creatorLevel,
                'skip_previous_levels' => true,
            ]);
            
            // Auto-approve all steps before creator's level
            $this->autoApprovePreviousLevels($request, $creatorLevel, $userId);
        }
        
        return $request;
    }

    /**
     * Auto-approve all levels before creator's level.
     *
     * @param ApprovalRequest $request
     * @param int $creatorLevel
     * @param int $userId
     * @return void
     */
    protected function autoApprovePreviousLevels(ApprovalRequest $request, int $creatorLevel, int $userId): void
    {
        $workflow = $request->workflow;
        $steps = $workflow->activeSteps()->where('sequence', '<', $creatorLevel)->get();
        
        foreach ($steps as $step) {
            ApprovalAction::recordAction(
                $request,
                $step,
                $userId,
                ApprovalAction::ACTION_APPROVED,
                'Auto-approved (creator is Level ' . $creatorLevel . ' approver)'
            );
        }
        
        // Set current step to creator's level
        $creatorStep = $workflow->activeSteps()->where('sequence', $creatorLevel)->first();
        if ($creatorStep) {
            $request->update(['current_step_id' => $creatorStep->id]);
        }
    }

    /**
     * Edit and resubmit an approval request.
     *
     * @param ApprovalRequest $request
     * @param Model $updatedModel
     * @param int $userId
     * @param string|null $remarks
     * @return ApprovalRequest
     */
    public function editAndResubmit(
        ApprovalRequest $request,
        Model $updatedModel,
        int $userId,
        ?string $remarks = null
    ): ApprovalRequest {
        // Record the edit action
        ApprovalAction::recordAction(
            $request,
            $request->currentStep,
            $userId,
            'edited',
            $remarks ?? 'Document edited and resubmitted'
        );
        
        // Update data snapshot
        $request->update([
            'data_snapshot' => $updatedModel->toArray(),
            'status' => ApprovalRequest::STATUS_SUBMITTED,
        ]);
        
        // Reset to first step
        $firstStep = $request->workflow->activeSteps()->first();
        if ($firstStep) {
            $request->update(['current_step_id' => $firstStep->id]);
        }
        
        return $request->fresh();
    }

    /**
     * Calculate approval progress percentage.
     *
     * @param ApprovalRequest $request
     * @return array
     */
    public function calculateApprovalProgress(ApprovalRequest $request): array
    {
        $workflow = $request->workflow;
        $totalSteps = $workflow->activeSteps()->count();
        
        if ($totalSteps === 0) {
            return [
                'total_steps' => 0,
                'completed_steps' => 0,
                'progress_percentage' => 0,
                'current_step_name' => null,
            ];
        }
        
        // Count approved steps
        $approvedSteps = ApprovalAction::where('approval_request_id', $request->id)
            ->where('action', ApprovalAction::ACTION_APPROVED)
            ->distinct('approval_step_id')
            ->count();
        
        $progressPercentage = round(($approvedSteps / $totalSteps) * 100, 2);
        
        return [
            'total_steps' => $totalSteps,
            'completed_steps' => $approvedSteps,
            'progress_percentage' => $progressPercentage,
            'current_step_name' => $request->currentStep?->name,
            'current_step_sequence' => $request->currentStep?->sequence,
        ];
    }

    /**
     * Get pending approvals for a user.
     */
    /**
     * Get pending approvals for a user.
     */
    public function getPendingApprovalsForUser(int $userId)
    {
        return ApprovalRequest::whereHas('currentStep', function ($query) use ($userId) {
            $query->whereHas('approvers', function ($q) use ($userId) {
                $q->where('user_id', $userId)
                  ->where('is_approved', false);
            });
        })
        ->where('status', ApprovalRequest::STATUS_SUBMITTED)
        ->with(['workflow', 'currentStep', 'requestable'])
        ->orderBy('submitted_at', 'desc')
        ->get();
    }

    /**
     * Get approval dashboard stats.
     */
    public function getDashboardStats(int $userId)
    {
        return [
            'pending' => ApprovalRequest::where('status', ApprovalRequest::STATUS_PENDING)->count(),
            'approved_today' => ApprovalRequest::whereDate('completed_at', today())
                ->where('status', ApprovalRequest::STATUS_APPROVED)
                ->count(),
            'rejected_this_month' => ApprovalRequest::whereMonth('rejected_at', now()->month)
                ->where('status', ApprovalRequest::STATUS_REJECTED)
                ->count(),
        ];
    }
}
