<?php

namespace AshiqFardus\ApprovalProcess\Http\Controllers\Web;

use AshiqFardus\ApprovalProcess\Http\Controllers\Controller;
use AshiqFardus\ApprovalProcess\Services\AnalyticsService;
use AshiqFardus\ApprovalProcess\Models\ApprovalRequest;
use AshiqFardus\ApprovalProcess\Models\Workflow;
use AshiqFardus\ApprovalProcess\Models\ApprovalBottleneck;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardWebController extends Controller
{
    protected AnalyticsService $analytics;

    public function __construct(AnalyticsService $analytics)
    {
        $this->analytics = $analytics;
    }

    /**
     * Show main dashboard.
     */
    public function index(): View
    {
        $stats = $this->analytics->getDashboardStats();
        
        // Recent requests
        $recentRequests = ApprovalRequest::with(['workflow', 'requestedBy', 'currentStep'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Active workflows
        $activeWorkflows = Workflow::where('is_active', true)
            ->withCount('requests')
            ->orderBy('name')
            ->get();

        // Pending approvals for current user
        $myPendingApprovals = ApprovalRequest::whereHas('currentStep.approvers', function ($q) {
            $q->where('approver_type', 'user')
              ->where('approver_id', auth()->id());
        })
        ->whereIn('status', ['submitted', 'in_progress'])
        ->with(['workflow', 'requestedBy'])
        ->limit(5)
        ->get();

        // Recent bottlenecks
        $bottlenecks = ApprovalBottleneck::unresolved()
            ->with(['workflow', 'step'])
            ->orderByDesc('severity')
            ->limit(5)
            ->get();

        return view('approval-process::dashboard.index', compact(
            'stats',
            'recentRequests',
            'activeWorkflows',
            'myPendingApprovals',
            'bottlenecks'
        ));
    }
}
