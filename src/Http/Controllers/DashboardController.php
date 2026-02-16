<?php

namespace AshiqFardus\ApprovalProcess\Http\Controllers;

use AshiqFardus\ApprovalProcess\Models\ApprovalRequest;
use AshiqFardus\ApprovalProcess\Services\ApprovalEngine;
use AshiqFardus\ApprovalProcess\Services\AnalyticsService;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    protected ApprovalEngine $engine;
    protected AnalyticsService $analytics;

    public function __construct(ApprovalEngine $engine, AnalyticsService $analytics)
    {
        $this->engine = $engine;
        $this->analytics = $analytics;
    }

    /**
     * Get dashboard statistics.
     */
    public function stats(): JsonResponse
    {
        $workflowId = request()->input('workflow_id');
        $stats = $this->analytics->getDashboardStats($workflowId);

        return response()->json($stats);
    }

    /**
     * Get pending approvals.
     */
    public function pending(): JsonResponse
    {
        $requests = ApprovalRequest::where('status', ApprovalRequest::STATUS_PENDING)
            ->orWhere('status', ApprovalRequest::STATUS_IN_REVIEW)
            ->with(['workflow', 'requestable', 'currentStep'])
            ->paginate(config('approval-process.ui.items_per_page'));

        return response()->json($requests);
    }

    /**
     * Get approved requests.
     */
    public function approved(): JsonResponse
    {
        $requests = ApprovalRequest::where('status', ApprovalRequest::STATUS_APPROVED)
            ->with(['workflow', 'requestable'])
            ->paginate(config('approval-process.ui.items_per_page'));

        return response()->json($requests);
    }

    /**
     * Get rejected requests.
     */
    public function rejected(): JsonResponse
    {
        $requests = ApprovalRequest::where('status', ApprovalRequest::STATUS_REJECTED)
            ->with(['workflow', 'requestable'])
            ->paginate(config('approval-process.ui.items_per_page'));

        return response()->json($requests);
    }
}
