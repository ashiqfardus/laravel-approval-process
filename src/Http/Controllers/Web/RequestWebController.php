<?php

namespace AshiqFardus\ApprovalProcess\Http\Controllers\Web;

use AshiqFardus\ApprovalProcess\Http\Controllers\Controller;
use AshiqFardus\ApprovalProcess\Models\ApprovalRequest;
use AshiqFardus\ApprovalProcess\Models\Workflow;
use AshiqFardus\ApprovalProcess\Services\ApprovalEngine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class RequestWebController extends Controller
{
    protected ApprovalEngine $approvalEngine;

    public function __construct(ApprovalEngine $approvalEngine)
    {
        $this->approvalEngine = $approvalEngine;
    }

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
            'workflow.steps.approvers',
            'requestedBy',
            'currentStep.approvers',
            'actions.user',
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

    /**
     * Edit and resubmit a request.
     */
    public function editAndResubmit(Request $request, int $requestId): JsonResponse
    {
        try {
            // Find the approval request
            $approvalRequest = ApprovalRequest::findOrFail($requestId);

            // Validate that the request can be edited
            if (!in_array($approvalRequest->status, ['rejected', 'sent_back'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only rejected or sent back requests can be edited and resubmitted.',
                ], 422);
            }

            // Validate that the user is the creator
            if ($approvalRequest->requested_by_user_id !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You can only edit your own requests.',
                ], 403);
            }

            // Validate input
            $validator = Validator::make($request->all(), [
                'data' => 'required|array',
                'comments' => 'nullable|string|max:1000',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Create a temporary model with the updated data for the engine
            $tempModel = new class extends \Illuminate\Database\Eloquent\Model {
                protected $guarded = [];
                public function toArray(): array {
                    return $this->attributes;
                }
            };
            $tempModel->fill($request->input('data'));
            
            // Edit and resubmit using the approval engine
            $updatedRequest = $this->approvalEngine->editAndResubmit(
                $approvalRequest,
                $tempModel,
                auth()->id(),
                $request->input('comments')
            );

            return response()->json([
                'success' => true,
                'message' => 'Request has been updated and resubmitted successfully!',
                'data' => [
                    'request_id' => $updatedRequest->id,
                    'status' => $updatedRequest->status,
                    'current_step' => $updatedRequest->currentStep?->name,
                ],
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Request not found.',
            ], 404);

        } catch (\Exception $e) {
            \Log::error('Edit and resubmit failed: ' . $e->getMessage(), [
                'request_id' => $requestId,
                'user_id' => auth()->id(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing your request. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
