<?php

namespace AshiqFardus\ApprovalProcess\Helpers;

use AshiqFardus\ApprovalProcess\Models\ApprovalRequest;

class ApprovalHelper
{
    /**
     * Get approval status badge HTML.
     */
    public static function statusBadge(string $status): string
    {
        $classes = [
            'approved' => 'bg-green-100 text-green-800',
            'rejected' => 'bg-red-100 text-red-800',
            'pending' => 'bg-yellow-100 text-yellow-800',
            'in-review' => 'bg-blue-100 text-blue-800',
            'draft' => 'bg-gray-100 text-gray-800',
        ];

        $icons = [
            'approved' => '✓',
            'rejected' => '✗',
            'pending' => '⏳',
            'in-review' => '👁',
            'draft' => '📝',
        ];

        $class = $classes[$status] ?? 'bg-gray-100 text-gray-800';
        $icon = $icons[$status] ?? '';

        return "<span class=\"px-3 py-1 text-xs font-medium rounded-full {$class}\">
                    {$icon} " . ucfirst(str_replace('-', ' ', $status)) . "
                </span>";
    }

    /**
     * Get human-readable approval action.
     */
    public static function formatAction(string $action): string
    {
        $actions = [
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'sent-back' => 'Sent Back',
            'held' => 'Held',
            'escalated' => 'Escalated',
            'delegated' => 'Delegated',
            'commented' => 'Commented',
        ];

        return $actions[$action] ?? ucfirst(str_replace('-', ' ', $action));
    }

    /**
     * Check if request can be edited.
     */
    public static function canEdit(ApprovalRequest $request): bool
    {
        return $request->canEdit();
    }

    /**
     * Check if request is pending.
     */
    public static function isPending(ApprovalRequest $request): bool
    {
        return $request->isPending();
    }

    /**
     * Get approval chain for a request.
     */
    public static function getApprovalChain(ApprovalRequest $request): array
    {
        return $request->workflow->activeSteps()
            ->with('approvers.assignedUser')
            ->get()
            ->toArray();
    }

    /**
     * Get SLA status for a request.
     */
    public static function getSLAStatus(ApprovalRequest $request): string
    {
        $step = $request->currentStep;

        if (!$step || !$step->sla_hours) {
            return 'no-sla';
        }

        $submittedAt = $request->submitted_at;
        $slaDeadline = $submittedAt->addHours($step->sla_hours);
        $now = now();

        if ($now > $slaDeadline) {
            return 'breached';
        }

        $percentageUsed = $now->diffInSeconds($submittedAt) / $slaDeadline->diffInSeconds($submittedAt) * 100;

        if ($percentageUsed >= 80) {
            return 'warning';
        }

        return 'on-track';
    }

    /**
     * Get approval statistics.
     */
    public static function getStatistics(): array
    {
        return [
            'total' => ApprovalRequest::count(),
            'pending' => ApprovalRequest::whereIn('status', [
                ApprovalRequest::STATUS_PENDING,
                ApprovalRequest::STATUS_IN_REVIEW,
                ApprovalRequest::STATUS_SUBMITTED
            ])->count(),
            'approved' => ApprovalRequest::where('status', ApprovalRequest::STATUS_APPROVED)->count(),
            'rejected' => ApprovalRequest::where('status', ApprovalRequest::STATUS_REJECTED)->count(),
            'average_cycle_time' => static::getAverageCycleTime(),
        ];
    }

    /**
     * Get average approval cycle time.
     */
    public static function getAverageCycleTime(): int
    {
        $approved = ApprovalRequest::where('status', ApprovalRequest::STATUS_APPROVED)
            ->whereNotNull('submitted_at')
            ->whereNotNull('completed_at')
            ->get();

        if ($approved->isEmpty()) {
            return 0;
        }

        $totalSeconds = $approved->sum(function ($request) {
            return $request->completed_at->diffInSeconds($request->submitted_at);
        });

        return (int) ($totalSeconds / $approved->count() / 3600); // Convert to hours
    }
}
