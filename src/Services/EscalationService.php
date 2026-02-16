<?php

namespace AshiqFardus\ApprovalProcess\Services;

use AshiqFardus\ApprovalProcess\Models\ApprovalRequest;
use AshiqFardus\ApprovalProcess\Models\ApprovalEscalation;
use AshiqFardus\ApprovalProcess\Models\ApprovalStep;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class EscalationService
{
    protected ApprovalNotificationService $notificationService;

    public function __construct(ApprovalNotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Check for overdue approvals and escalate them.
     *
     * @return int Number of escalations performed
     */
    public function checkOverdueApprovals(): int
    {
        $overdueRequests = ApprovalRequest::where('status', ApprovalRequest::STATUS_PENDING)
            ->whereNotNull('sla_deadline')
            ->where('sla_deadline', '<', now())
            ->with('currentStep')
            ->get();

        $escalated = 0;
        foreach ($overdueRequests as $request) {
            $this->escalateRequest($request, 'sla_timeout');
            $escalated++;
        }

        return $escalated;
    }

    /**
     * Escalate a request to the next level or manager.
     *
     * @param ApprovalRequest $request
     * @param string $reason
     * @return void
     */
    public function escalateRequest(ApprovalRequest $request, string $reason = 'manual'): void
    {
        $currentStep = $request->currentStep;
        if (!$currentStep) {
            return;
        }

        $nextStep = $currentStep->getNextStep();
        if (!$nextStep) {
            // No next step, escalate to workflow owner or admin
            return;
        }

        // Get current approvers
        $currentApprovers = $currentStep->approvers;

        // Create escalation record
        foreach ($currentApprovers as $approver) {
            foreach ($nextStep->approvers as $nextApprover) {
                ApprovalEscalation::create([
                    'approval_request_id' => $request->id,
                    'from_user_id' => $approver->user_id,
                    'to_user_id' => $nextApprover->user_id,
                    'from_level' => $currentStep->sequence,
                    'to_level' => $nextStep->sequence,
                    'reason' => $reason,
                    'remarks' => $reason === 'sla_timeout' 
                        ? 'Automatically escalated due to SLA timeout' 
                        : null,
                ]);
            }
        }

        // Move to next step
        $request->update(['current_step_id' => $nextStep->id]);
        $request->updateSLADeadline();

        // Notify new approvers
        $this->notificationService->notifyApprovers($request, 'escalated');

        // Notify creator
        $this->notificationService->notifyCreator(
            $request,
            'escalated',
            "Your request has been escalated to {$nextStep->name}"
        );
    }

    /**
     * Send reminder notifications for pending approvals.
     *
     * @return int Number of reminders sent
     */
    public function sendReminders(): int
    {
        // Send reminders for requests that are halfway to SLA deadline
        $requests = ApprovalRequest::where('status', ApprovalRequest::STATUS_PENDING)
            ->whereNotNull('sla_deadline')
            ->where('sla_deadline', '>', now())
            ->where(function ($q) {
                // Send reminder if no reminder sent yet, or last reminder was > 6 hours ago
                $q->whereNull('last_reminder_sent')
                  ->orWhere('last_reminder_sent', '<', now()->subHours(6));
            })
            ->get();

        $sent = 0;
        foreach ($requests as $request) {
            // Check if we're past halfway point to deadline
            $hoursRemaining = now()->diffInHours($request->sla_deadline, false);
            $totalHours = $request->currentStep?->sla_hours ?? 24;
            
            if ($hoursRemaining <= ($totalHours / 2)) {
                $this->notificationService->sendReminder($request);
                $sent++;
            }
        }

        return $sent;
    }

    /**
     * Calculate SLA deadline for a step.
     *
     * @param ApprovalStep $step
     * @return Carbon|null
     */
    public function calculateSLA(ApprovalStep $step): ?Carbon
    {
        if (!$step->sla_hours) {
            return null;
        }

        return now()->addHours($step->sla_hours);
    }

    /**
     * Get escalation history for a request.
     *
     * @param ApprovalRequest $request
     * @return Collection
     */
    public function getEscalationHistory(ApprovalRequest $request): Collection
    {
        return ApprovalEscalation::where('approval_request_id', $request->id)
            ->with(['fromUser', 'toUser'])
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
