<?php

namespace AshiqFardus\ApprovalProcess\Http\Controllers\Web;

use AshiqFardus\ApprovalProcess\Http\Controllers\Controller;
use AshiqFardus\ApprovalProcess\Models\ApprovalRequest;
use AshiqFardus\ApprovalProcess\Models\Workflow;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RequestWebController extends Controller
{
    /**
     * Show all requests.
     */
    public function index(): View
    {
        $query = ApprovalRequest::with(['workflow', 'requestedBy', 'currentStep']);

        // Apply filters
        if (request()->has('status')) {
            $query->where('status', request()->input('status'));
        }

        if (request()->has('workflow_id')) {
            $query->where('workflow_id', request()->input('workflow_id'));
        }

        $requests = $query->orderBy('created_at', 'desc')->paginate(15);
        $workflows = Workflow::where('is_active', true)->get();

        return view('approval-process::requests.index', compact('requests', 'workflows'));
    }

    /**
     * Show create request form.
     */
    public function create(): View
    {
        $workflows = Workflow::where('is_active', true)->get();

        return view('approval-process::requests.create', compact('workflows'));
    }

    /**
     * Show request details.
     */
    public function show(int $request): View
    {
        $request = ApprovalRequest::with([
            'workflow.steps',
            'requestedBy',
            'currentStep',
            'actions.user',
            'attachments',
            'signatures',
        ])->findOrFail($request);

        return view('approval-process::requests.show', compact('request'));
    }

    /**
     * Show request timeline.
     */
    public function timeline(int $request): View
    {
        $request = ApprovalRequest::with([
            'workflow',
            'actions.user',
            'actions.step',
            'changeLogs',
        ])->findOrFail($request);

        return view('approval-process::requests.timeline', compact('request'));
    }

    /**
     * Show my approvals.
     */
    public function myApprovals(): View
    {
        $requests = ApprovalRequest::whereHas('currentStep.approvers', function ($q) {
            $q->where('approver_type', 'user')
              ->where('approver_id', auth()->id());
        })
        ->whereIn('status', ['submitted', 'in_progress'])
        ->with(['workflow', 'requestedBy', 'currentStep'])
        ->orderBy('created_at', 'desc')
        ->paginate(15);

        return view('approval-process::requests.my-approvals', compact('requests'));
    }

    /**
     * Show my requests.
     */
    public function myRequests(): View
    {
        $requests = ApprovalRequest::where('requested_by_user_id', auth()->id())
            ->with(['workflow', 'currentStep'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('approval-process::requests.my-requests', compact('requests'));
    }
}
