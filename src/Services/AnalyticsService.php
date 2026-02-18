<?php

namespace AshiqFardus\ApprovalProcess\Services;

use AshiqFardus\ApprovalProcess\Models\ApprovalRequest;
use AshiqFardus\ApprovalProcess\Models\ApprovalAction;
use AshiqFardus\ApprovalProcess\Models\Workflow;
use AshiqFardus\ApprovalProcess\Models\WorkflowMetric;
use AshiqFardus\ApprovalProcess\Models\UserMetric;
use AshiqFardus\ApprovalProcess\Models\ApprovalBottleneck;
use AshiqFardus\ApprovalProcess\Models\ApprovalAnalytic;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AnalyticsService
{
    /**
     * Calculate and store workflow metrics for a date.
     */
    public function calculateWorkflowMetrics(int $workflowId, Carbon $date): WorkflowMetric
    {
        $workflow = Workflow::findOrFail($workflowId);
        
        $requests = ApprovalRequest::where('workflow_id', $workflowId)
            ->whereDate('created_at', $date)
            ->get();

        $totalRequests = $requests->count();
        $approvedRequests = $requests->where('status', 'approved')->count();
        $rejectedRequests = $requests->where('status', 'rejected')->count();
        $pendingRequests = $requests->whereIn('status', ['submitted', 'in_progress'])->count();
        $cancelledRequests = $requests->where('status', 'cancelled')->count();

        // Calculate average approval time
        $completedRequests = $requests->whereIn('status', ['approved', 'rejected']);
        $avgApprovalTime = $completedRequests->avg(function ($request) {
            if ($request->completed_at && $request->created_at) {
                return $request->created_at->diffInHours($request->completed_at);
            }
            return null;
        });

        // Calculate rates
        $approvalRate = $totalRequests > 0 ? ($approvedRequests / $totalRequests) * 100 : 0;
        $rejectionRate = $totalRequests > 0 ? ($rejectedRequests / $totalRequests) * 100 : 0;

        // Calculate SLA compliance
        $slaCompliant = $completedRequests->filter(function ($request) {
            return !$request->is_overdue;
        })->count();
        $slaComplianceRate = $completedRequests->count() > 0 
            ? ($slaCompliant / $completedRequests->count()) * 100 
            : 100;

        return WorkflowMetric::updateOrCreate(
            [
                'workflow_id' => $workflowId,
                'metric_date' => $date->toDateString(),
            ],
            [
                'total_requests' => $totalRequests,
                'approved_requests' => $approvedRequests,
                'rejected_requests' => $rejectedRequests,
                'pending_requests' => $pendingRequests,
                'cancelled_requests' => $cancelledRequests,
                'avg_approval_time_hours' => $avgApprovalTime,
                'approval_rate' => $approvalRate,
                'rejection_rate' => $rejectionRate,
                'sla_compliance_rate' => $slaComplianceRate,
            ]
        );
    }

    /**
     * Calculate and store user metrics for a date.
     */
    public function calculateUserMetrics(int $userId, Carbon $date): UserMetric
    {
        // Requests submitted
        $requestsSubmitted = ApprovalRequest::where('requested_by_user_id', $userId)
            ->whereDate('created_at', $date)
            ->count();

        // Approvals given
        $approvalsGiven = ApprovalAction::where('user_id', $userId)
            ->where('action', 'approved')
            ->whereDate('created_at', $date)
            ->count();

        // Rejections given
        $rejectionsGiven = ApprovalAction::where('user_id', $userId)
            ->where('action', 'rejected')
            ->whereDate('created_at', $date)
            ->count();

        // Pending approvals
        $pendingApprovals = ApprovalRequest::whereHas('currentStep.approvers', function ($q) use ($userId) {
            $q->where('approver_type', 'user')
              ->where('approver_id', $userId);
        })
        ->whereIn('status', ['submitted', 'in_progress'])
        ->count();

        // Average response time
        $actions = ApprovalAction::where('user_id', $userId)
            ->whereDate('created_at', $date)
            ->with('request')
            ->get();

        $avgResponseTime = $actions->avg(function ($action) {
            $request = $action->request;
            if ($request && $request->created_at) {
                return $request->created_at->diffInHours($action->created_at);
            }
            return null;
        });

        // Overdue approvals
        $overdueApprovals = ApprovalRequest::whereHas('currentStep.approvers', function ($q) use ($userId) {
            $q->where('approver_type', 'user')
              ->where('approver_id', $userId);
        })
        ->where('sla_deadline', '<', now())
        ->where('status', 'pending')
        ->count();

        return UserMetric::updateOrCreate(
            [
                'user_id' => $userId,
                'metric_date' => $date->toDateString(),
            ],
            [
                'requests_submitted' => $requestsSubmitted,
                'approvals_given' => $approvalsGiven,
                'rejections_given' => $rejectionsGiven,
                'pending_approvals' => $pendingApprovals,
                'avg_response_time_hours' => $avgResponseTime,
                'overdue_approvals' => $overdueApprovals,
            ]
        );
    }

    /**
     * Detect and record bottlenecks.
     */
    public function detectBottlenecks(Carbon $date): array
    {
        $bottlenecks = [];

        $workflows = Workflow::where('is_active', true)->get();

        foreach ($workflows as $workflow) {
            foreach ($workflow->steps as $step) {
                $pendingRequests = ApprovalRequest::where('workflow_id', $workflow->id)
                    ->where('current_step_id', $step->id)
                    ->whereIn('status', ['submitted', 'in_progress'])
                    ->get();

                $pendingCount = $pendingRequests->count();

                if ($pendingCount >= 10) { // Threshold for bottleneck
                    $avgWaitTime = $pendingRequests->avg(function ($request) {
                        return $request->created_at->diffInHours(now());
                    });

                    $severity = ApprovalBottleneck::calculateSeverity($pendingCount, $avgWaitTime);

                    $recommendation = $this->generateBottleneckRecommendation($pendingCount, $avgWaitTime, $step);

                    $bottleneck = ApprovalBottleneck::updateOrCreate(
                        [
                            'workflow_id' => $workflow->id,
                            'step_id' => $step->id,
                            'detected_date' => $date->toDateString(),
                        ],
                        [
                            'pending_count' => $pendingCount,
                            'avg_wait_time_hours' => $avgWaitTime,
                            'severity' => $severity,
                            'recommendation' => $recommendation,
                        ]
                    );

                    $bottlenecks[] = $bottleneck;
                }
            }
        }

        return $bottlenecks;
    }

    /**
     * Generate recommendation for bottleneck.
     */
    protected function generateBottleneckRecommendation(int $pendingCount, float $avgWaitTime, $step): string
    {
        $recommendations = [];

        if ($pendingCount >= 30) {
            $recommendations[] = "Add additional approvers to step '{$step->name}'";
        }

        if ($avgWaitTime >= 48) {
            $recommendations[] = "Enable delegation for this step";
            $recommendations[] = "Send reminder notifications";
        }

        if ($step->approval_type === 'serial' && $pendingCount >= 20) {
            $recommendations[] = "Consider changing to parallel approval";
        }

        return implode('. ', $recommendations) ?: 'Monitor situation closely';
    }

    /**
     * Get dashboard statistics.
     */
    public function getDashboardStats(?int $workflowId = null): array
    {
        $query = ApprovalRequest::query();
        
        if ($workflowId) {
            $query->where('workflow_id', $workflowId);
        }

        $totalRequests = $query->count();
        $pendingRequests = (clone $query)->whereIn('status', ['submitted', 'in_progress'])->count();
        $approvedRequests = (clone $query)->where('status', 'approved')->count();
        $rejectedRequests = (clone $query)->where('status', 'rejected')->count();
        $overdueRequests = (clone $query)
            ->where('sla_deadline', '<', now())
            ->where('status', 'pending')
            ->count();

        // Average approval time (last 30 days)
        $avgApprovalTime = (clone $query)
            ->where('status', 'approved')
            ->where('created_at', '>=', now()->subDays(30))
            ->get()
            ->avg(function ($request) {
                if ($request->completed_at) {
                    return $request->created_at->diffInHours($request->completed_at);
                }
                return null;
            });

        // Approval rate (last 30 days)
        $recentTotal = (clone $query)->where('created_at', '>=', now()->subDays(30))->count();
        $recentApproved = (clone $query)
            ->where('status', 'approved')
            ->where('created_at', '>=', now()->subDays(30))
            ->count();
        $approvalRate = $recentTotal > 0 ? ($recentApproved / $recentTotal) * 100 : 0;

        return [
            'total_requests' => $totalRequests,
            'pending_requests' => $pendingRequests,
            'approved_requests' => $approvedRequests,
            'rejected_requests' => $rejectedRequests,
            'overdue_requests' => $overdueRequests,
            'avg_approval_time_hours' => round($avgApprovalTime ?? 0, 2),
            'approval_rate' => round($approvalRate, 2),
        ];
    }

    /**
     * Get trend data for a metric.
     */
    public function getTrendData(string $metricType, string $period, Carbon $start, Carbon $end): array
    {
        $analytics = ApprovalAnalytic::byMetricType($metricType)
            ->byPeriod($period)
            ->dateRange($start, $end)
            ->orderBy('period_start')
            ->get();

        return $analytics->map(function ($analytic) {
            return [
                'date' => $analytic->period_start->format('Y-m-d'),
                'value' => $analytic->value,
            ];
        })->toArray();
    }

    /**
     * Get top performers (users with most approvals).
     */
    public function getTopPerformers(int $limit = 10, Carbon $start = null, Carbon $end = null): array
    {
        $start = $start ?? now()->subDays(30);
        $end = $end ?? now();

        return UserMetric::dateRange($start, $end)
            ->select('user_id', DB::raw('SUM(approvals_given) as total_approvals'))
            ->groupBy('user_id')
            ->orderByDesc('total_approvals')
            ->limit($limit)
            ->with('user')
            ->get()
            ->map(function ($metric) {
                return [
                    'user_id' => $metric->user_id,
                    'user_name' => $metric->user->name ?? 'Unknown',
                    'total_approvals' => $metric->total_approvals,
                ];
            })
            ->toArray();
    }

    /**
     * Get workflow comparison data.
     */
    public function compareWorkflows(array $workflowIds, Carbon $start, Carbon $end): array
    {
        $comparison = [];

        foreach ($workflowIds as $workflowId) {
            $metrics = WorkflowMetric::forWorkflow($workflowId)
                ->dateRange($start, $end)
                ->get();

            $workflow = Workflow::find($workflowId);

            $comparison[] = [
                'workflow_id' => $workflowId,
                'workflow_name' => $workflow->name ?? 'Unknown',
                'total_requests' => $metrics->sum('total_requests'),
                'approval_rate' => $metrics->avg('approval_rate'),
                'avg_approval_time' => $metrics->avg('avg_approval_time_hours'),
                'sla_compliance' => $metrics->avg('sla_compliance_rate'),
            ];
        }

        return $comparison;
    }
}
