<?php

namespace AshiqFardus\ApprovalProcess\Http\Controllers;

use AshiqFardus\ApprovalProcess\Models\ApprovalRequest;
use AshiqFardus\ApprovalProcess\Services\ApprovalEngine;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    protected ApprovalEngine $engine;

    public function __construct(ApprovalEngine $engine)
    {
        $this->engine = $engine;
    }

    /**
     * Get dashboard statistics.
     */
    public function stats(): JsonResponse
    {
        $stats = $this->engine->getDashboardStats(auth()->id());

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
