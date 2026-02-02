<?php

namespace AshiqFardus\ApprovalProcess\Http\Controllers;

use AshiqFardus\ApprovalProcess\Models\ApprovalRequest;
use AshiqFardus\ApprovalProcess\Http\Requests\ApprovalRequestRequest;
use AshiqFardus\ApprovalProcess\Services\ApprovalEngine;
use Illuminate\Http\JsonResponse;

class ApprovalRequestController extends Controller
{
    protected ApprovalEngine $engine;

    public function __construct(ApprovalEngine $engine)
    {
        $this->engine = $engine;
    }

    /**
     * Display a listing of approval requests.
     */
    public function index(): JsonResponse
    {
        $requests = ApprovalRequest::with(['workflow', 'currentStep', 'actions'])
            ->paginate(config('approval-process.ui.items_per_page'));

        return response()->json($requests);
    }

    /**
     * Store a newly created approval request.
     */
    public function store(ApprovalRequestRequest $request): JsonResponse
    {
        $approvalRequest = ApprovalRequest::create($request->validated());

        return response()->json($approvalRequest, 201);
    }

    /**
     * Display the specified approval request.
     */
    public function show(ApprovalRequest $approvalRequest): JsonResponse
    {
        $approvalRequest->load(['workflow.steps', 'currentStep.approvers', 'actions.user']);

        return response()->json($approvalRequest);
    }

    /**
     * Update the specified approval request.
     */
    public function update(ApprovalRequestRequest $request, ApprovalRequest $approvalRequest): JsonResponse
    {
        if (!$approvalRequest->canEdit()) {
            return response()->json(['message' => 'Request cannot be edited'], 422);
        }

        $approvalRequest->update($request->validated());

        return response()->json($approvalRequest);
    }

    /**
     * Submit the approval request.
     */
    public function submit(ApprovalRequest $approvalRequest): JsonResponse
    {
        $approvalRequest->submit();

        return response()->json($approvalRequest);
    }

    /**
     * Approve the request.
     */
    public function approve(ApprovalRequest $approvalRequest): JsonResponse
    {
        $this->engine->approve(
            $approvalRequest,
            auth()->id(),
            request()->input('remarks')
        );

        $approvalRequest->refresh();

        return response()->json($approvalRequest);
    }

    /**
     * Reject the request.
     */
    public function reject(ApprovalRequest $approvalRequest): JsonResponse
    {
        $this->engine->reject(
            $approvalRequest,
            auth()->id(),
            request()->input('reason', 'No reason provided'),
            request()->input('remarks')
        );

        $approvalRequest->refresh();

        return response()->json($approvalRequest);
    }

    /**
     * Send back the request.
     */
    public function sendBack(ApprovalRequest $approvalRequest): JsonResponse
    {
        $this->engine->sendBack(
            $approvalRequest,
            auth()->id(),
            request()->input('remarks')
        );

        $approvalRequest->refresh();

        return response()->json($approvalRequest);
    }

    /**
     * Hold the request.
     */
    public function hold(ApprovalRequest $approvalRequest): JsonResponse
    {
        $this->engine->hold(
            $approvalRequest,
            auth()->id(),
            request()->input('remarks')
        );

        $approvalRequest->refresh();

        return response()->json($approvalRequest);
    }

    /**
     * Cancel the request.
     */
    public function cancel(ApprovalRequest $approvalRequest): JsonResponse
    {
        $approvalRequest->cancel();

        return response()->json($approvalRequest);
    }

    /**
     * Resubmit the request after rejection.
     */
    public function resubmit(ApprovalRequest $approvalRequest): JsonResponse
    {
        if ($approvalRequest->status !== ApprovalRequest::STATUS_REJECTED) {
            return response()->json(['message' => 'Only rejected requests can be resubmitted'], 422);
        }

        $approvalRequest->update([
            'status' => ApprovalRequest::STATUS_SUBMITTED,
            'rejected_at' => null,
            'rejection_reason' => null,
        ]);

        return response()->json($approvalRequest);
    }

    /**
     * Delete the specified approval request.
     */
    public function destroy(ApprovalRequest $approvalRequest): JsonResponse
    {
        $approvalRequest->delete();

        return response()->json(null, 204);
    }
}
