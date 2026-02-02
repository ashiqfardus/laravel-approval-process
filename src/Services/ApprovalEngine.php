<?php

namespace AshiqFardus\ApprovalProcess\Services;

use AshiqFardus\ApprovalProcess\Models\ApprovalRequest;
use AshiqFardus\ApprovalProcess\Models\ApprovalStep;
use AshiqFardus\ApprovalProcess\Models\ApprovalAction;
use AshiqFardus\ApprovalProcess\Models\Workflow;
use Illuminate\Database\Eloquent\Model;

class ApprovalEngine
{
    /**
     * Submit a request for approval.
     */
    public function submitRequest(Model $model, int $userId, array $metadata = []): ApprovalRequest
    {
        $workflow = Workflow::where('model_type', $model::class)
            ->where('is_active', true)
            ->first();

        if (!$workflow) {
            throw new \Exception("No active workflow found for {$model::class}");
        }

        $request = ApprovalRequest::create([
            'workflow_id' => $workflow->id,
            'requestable_type' => $model::class,
            'requestable_id' => $model->id,
            'requested_by_user_id' => $userId,
            'status' => ApprovalRequest::STATUS_SUBMITTED,
            'data_snapshot' => $model->toArray(),
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

        // Record the action
        ApprovalAction::recordAction(
            $request,
            $step,
            $userId,
            ApprovalAction::ACTION_APPROVED,
            $remarks
        );

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
        $approvers = $step->approvers;
        $approvedCount = $approvers->where('is_approved', true)->count();
        $totalCount = $approvers->count();

        if ($step->isSerial()) {
            // For serial, check if all have approved
            if ($approvedCount === $totalCount) {
                $this->moveToNextStep($request, $step);
            }
        } elseif ($step->isParallel()) {
            // For parallel, all must approve
            if ($approvedCount === $totalCount) {
                $this->moveToNextStep($request, $step);
            }
        }
    }

    /**
     * Move request to next step.
     */
    protected function moveToNextStep(ApprovalRequest $request, ApprovalStep $currentStep): void
    {
        $nextStep = $currentStep->getNextStep();

        if ($nextStep) {
            $request->update(['current_step_id' => $nextStep->id]);
        } else {
            // All steps completed
            $request->update([
                'status' => ApprovalRequest::STATUS_APPROVED,
                'completed_at' => now(),
            ]);
        }
    }

    /**
     * Get pending approvals for a user.
     */
    public function getPendingApprovalsForUser(int $userId)
    {
        // Implementation to get pending approvals
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
