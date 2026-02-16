<?php

namespace AshiqFardus\ApprovalProcess\Http\Controllers;

use AshiqFardus\ApprovalProcess\Models\ApprovalRequest;
use AshiqFardus\ApprovalProcess\Http\Requests\ApprovalRequestRequest;
use AshiqFardus\ApprovalProcess\Services\ApprovalEngine;
use AshiqFardus\ApprovalProcess\Services\ChangeTrackingService;
use AshiqFardus\ApprovalProcess\Services\ChangeHistoryFormatter;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\Model;

class ApprovalRequestController extends Controller
{
    protected ApprovalEngine $engine;
    protected ChangeTrackingService $changeTracking;
    protected ChangeHistoryFormatter $formatter;

    public function __construct(
        ApprovalEngine $engine,
        ChangeTrackingService $changeTracking,
        ChangeHistoryFormatter $formatter
    ) {
        $this->engine = $engine;
        $this->changeTracking = $changeTracking;
        $this->formatter = $formatter;
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

        $oldSnapshot = $approvalRequest->data_snapshot ?? [];
        $approvalRequest->update($request->validated());
        $newSnapshot = $approvalRequest->fresh()->data_snapshot ?? [];

        // Track changes
        $this->changeTracking->trackChanges(
            $approvalRequest,
            $oldSnapshot,
            $newSnapshot,
            auth()->id()
        );

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

        $approvalRequest->resubmit();

        return response()->json($approvalRequest);
    }

    /**
     * Edit and resubmit the request with updated data.
     * Works for both model-based (updates the requestable model) and query-based approvals
     * (updates data_snapshot and QueryApprovalRequest.result_snapshot).
     */
    public function editAndResubmit(ApprovalRequest $approvalRequest): JsonResponse
    {
        if (!$approvalRequest->canEdit()) {
            return response()->json([
                'message' => 'Request cannot be edited. Only draft or rejected requests can be edited.'
            ], 422);
        }

        $request = request();
        $updatedData = $request->input('data_snapshot', []);
        $remarks = $request->input('remarks', 'Document edited and resubmitted');

        // Get the requestable model if it exists
        $requestable = $approvalRequest->requestable;
        $oldSnapshot = $approvalRequest->data_snapshot ?? [];

        if ($requestable instanceof Model) {
            // Create a temporary model instance with old data for comparison
            $oldModel = clone $requestable;
            foreach ($oldSnapshot as $key => $value) {
                if (array_key_exists($key, $oldModel->getAttributes())) {
                    $oldModel->setAttribute($key, $value);
                }
            }

            // Update the actual model
            $requestable->fill($updatedData);
            $requestable->save();

            // Track changes
            $this->changeTracking->trackModelChanges(
                $approvalRequest,
                $oldModel,
                $requestable,
                auth()->id()
            );

            // Use engine's editAndResubmit method
            $approvalRequest = $this->engine->editAndResubmit(
                $approvalRequest,
                $requestable,
                auth()->id(),
                $remarks
            );
        } else {
            // For query-based or non-model requests: update snapshot and keep QueryApprovalRequest in sync
            // Track changes
            $this->changeTracking->trackChanges(
                $approvalRequest,
                $oldSnapshot,
                $updatedData,
                auth()->id()
            );

            // Update and resubmit
            $approvalRequest->update([
                'data_snapshot' => $updatedData,
                'status' => ApprovalRequest::STATUS_SUBMITTED,
                'rejected_at' => null,
                'rejection_reason' => null,
            ]);

            // Keep query-based approval snapshot in sync (result_snapshot = submitted result data)
            $queryApproval = $approvalRequest->queryApproval;
            if ($queryApproval) {
                $resultSnapshot = isset($updatedData['data']) ? $updatedData['data'] : $updatedData;
                $queryApproval->update(['result_snapshot' => $resultSnapshot]);
            }

            // Reset to first step
            $firstStep = $approvalRequest->workflow->activeSteps()->first();
            if ($firstStep) {
                $approvalRequest->update(['current_step_id' => $firstStep->id]);
            }
        }

        return response()->json($approvalRequest->fresh());
    }

    /**
     * Get change history for the request.
     */
    public function changeHistory(ApprovalRequest $approvalRequest): JsonResponse
    {
        $format = request()->input('format', 'json'); // 'json', 'text', 'html'
        $groupBy = request()->input('group_by'); // 'date', 'user', 'field', null

        if ($format === 'text') {
            $history = $this->formatter->formatRequestHistory($approvalRequest, [
                'group_by' => $groupBy
            ]);
            return response()->json(['history' => $history], 200, [], JSON_UNESCAPED_UNICODE);
        }

        if ($format === 'html') {
            $history = $this->formatter->formatAsHtml($approvalRequest);
            return response()->json(['history' => $history], 200, [], JSON_UNESCAPED_UNICODE);
        }

        // JSON format (default)
        $changes = \AshiqFardus\ApprovalProcess\Models\ApprovalChangeLog::getChangesForRequest($approvalRequest);
        $summary = $this->changeTracking->getChangeSummary($approvalRequest);

        return response()->json([
            'summary' => $summary,
            'changes' => $changes->map(function ($change) {
                return [
                    'id' => $change->id,
                    'field_name' => $change->field_name,
                    'old_value' => $change->old_value,
                    'new_value' => $change->new_value,
                    'user' => $change->user ? [
                        'id' => $change->user->id,
                        'name' => $change->user->name,
                    ] : null,
                    'formatted' => $this->formatter->formatChange($change),
                    'created_at' => $change->created_at->toIso8601String(),
                ];
            }),
        ]);
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
