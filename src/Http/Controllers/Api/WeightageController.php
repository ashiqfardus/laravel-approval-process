<?php

namespace AshiqFardus\ApprovalProcess\Http\Controllers\Api;

use AshiqFardus\ApprovalProcess\Models\ApprovalStep;
use AshiqFardus\ApprovalProcess\Models\ApprovalRequest;
use AshiqFardus\ApprovalProcess\Models\Approver;
use AshiqFardus\ApprovalProcess\Services\WeightageCalculator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;

class WeightageController extends Controller
{
    protected WeightageCalculator $calculator;

    public function __construct(WeightageCalculator $calculator)
    {
        $this->calculator = $calculator;
    }

    /**
     * Get approval breakdown for a step.
     *
     * GET /api/approval-process/steps/{step}/weightage/breakdown
     */
    public function getStepBreakdown($stepId): JsonResponse
    {
        // Query with relationships
        $step = ApprovalStep::with('approvers')->find($stepId);
        
        if (!$step) {
            return response()->json([
                'success' => false,
                'message' => 'Step not found',
            ], 404);
        }
        
        $breakdown = $this->calculator->getApprovalBreakdown($step);

        return response()->json([
            'success' => true,
            'data' => $breakdown,
        ]);
    }

    /**
     * Get approval breakdown for a request's current step.
     *
     * GET /api/approval-process/requests/{request}/weightage/breakdown
     */
    public function getRequestBreakdown($requestId): JsonResponse
    {
        $request = ApprovalRequest::with('currentStep.approvers')->find($requestId);
        
        if (!$request) {
            return response()->json([
                'success' => false,
                'message' => 'Request not found',
            ], 404);
        }
        
        if (!$request->currentStep) {
            return response()->json([
                'success' => false,
                'message' => 'Request has no current step',
            ], 404);
        }

        $breakdown = $this->calculator->getApprovalBreakdown($request->currentStep);

        return response()->json([
            'success' => true,
            'data' => $breakdown,
        ]);
    }

    /**
     * Get remaining approvals needed for a step.
     *
     * GET /api/approval-process/steps/{step}/weightage/remaining
     */
    public function getRemainingApprovals($stepId): JsonResponse
    {
        $step = ApprovalStep::with('approvers')->find($stepId);
        
        if (!$step) {
            return response()->json([
                'success' => false,
                'message' => 'Step not found',
            ], 404);
        }
        
        $remaining = $this->calculator->getRemainingApprovalsNeeded($step);

        return response()->json([
            'success' => true,
            'data' => $remaining,
        ]);
    }

    /**
     * Update minimum approval percentage for a step.
     *
     * PUT /api/approval-process/steps/{step}/weightage/minimum-percentage
     */
    public function updateMinimumPercentage(Request $request, $stepId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'minimum_percentage' => 'required|integer|min:0|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $step = ApprovalStep::find($stepId);
        
        if (!$step) {
            return response()->json([
                'success' => false,
                'message' => 'Step not found',
            ], 404);
        }

        $step->update([
            'minimum_approval_percentage' => $request->input('minimum_percentage'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Minimum approval percentage updated successfully',
            'data' => [
                'step_id' => $step->id,
                'minimum_approval_percentage' => $step->minimum_approval_percentage,
            ],
        ]);
    }

    /**
     * Update approver weightage.
     *
     * PUT /api/approval-process/approvers/{approver}/weightage
     */
    public function updateApproverWeightage(Request $request, $approverId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'weightage' => 'required|integer|min:0|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $approver = Approver::find($approverId);
        
        if (!$approver) {
            return response()->json([
                'success' => false,
                'message' => 'Approver not found',
            ], 404);
        }

        $approver->update([
            'weightage' => $request->input('weightage'),
        ]);

        // Recalculate step breakdown with fresh query
        $step = ApprovalStep::with('approvers')->find($approver->approval_step_id);
        $breakdown = $step ? $this->calculator->getApprovalBreakdown($step) : null;

        return response()->json([
            'success' => true,
            'message' => 'Approver weightage updated successfully',
            'data' => [
                'approver_id' => $approver->id,
                'weightage' => $approver->weightage,
                'step_breakdown' => $breakdown,
            ],
        ]);
    }

    /**
     * Bulk update approver weightages for a step.
     *
     * PUT /api/approval-process/steps/{step}/weightage/bulk-update
     */
    public function bulkUpdateWeightages(Request $request, $stepId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'approvers' => 'required|array',
            'approvers.*.id' => 'required|exists:approval_approvers,id',
            'approvers.*.weightage' => 'required|integer|min:0|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $step = ApprovalStep::find($stepId);
        
        if (!$step) {
            return response()->json([
                'success' => false,
                'message' => 'Step not found',
            ], 404);
        }

        $approvers = $request->input('approvers');

        foreach ($approvers as $approverData) {
            Approver::where('id', $approverData['id'])
                ->where('approval_step_id', $step->id)
                ->update(['weightage' => $approverData['weightage']]);
        }

        // Get updated breakdown (refresh the step with approvers)
        $step = ApprovalStep::with('approvers')->find($step->id);
        $breakdown = $this->calculator->getApprovalBreakdown($step);

        return response()->json([
            'success' => true,
            'message' => 'Weightages updated successfully',
            'data' => $breakdown,
        ]);
    }

    /**
     * Validate weightage distribution for a step.
     *
     * POST /api/approval-process/steps/{step}/weightage/validate
     */
    public function validateDistribution(Request $request, $stepId): JsonResponse
    {
        $step = ApprovalStep::with('approvers')->find($stepId);
        
        if (!$step) {
            return response()->json([
                'success' => false,
                'message' => 'Step not found',
            ], 404);
        }
        
        $approvers = $step->approvers->map(function ($approver) {
            return [
                'id' => $approver->id,
                'weightage' => $approver->weightage,
            ];
        })->toArray();

        $validation = $this->calculator->validateWeightageDistribution($approvers);

        return response()->json([
            'success' => true,
            'data' => $validation,
        ]);
    }

    /**
     * Get suggested weightage distribution.
     *
     * POST /api/approval-process/weightage/suggest
     */
    public function suggestDistribution(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'approver_count' => 'required|integer|min:1|max:20',
            'strategy' => 'required|in:equal,hierarchical,majority-one',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $distribution = $this->calculator->suggestWeightageDistribution(
            $request->input('approver_count'),
            $request->input('strategy')
        );

        return response()->json([
            'success' => true,
            'data' => [
                'strategy' => $request->input('strategy'),
                'approver_count' => $request->input('approver_count'),
                'distribution' => $distribution,
                'total' => array_sum($distribution),
            ],
        ]);
    }

    /**
     * Get approver percentages for a step.
     *
     * GET /api/approval-process/steps/{step}/weightage/percentages
     */
    public function getApproverPercentages($stepId): JsonResponse
    {
        $step = ApprovalStep::with('approvers')->find($stepId);
        
        if (!$step) {
            return response()->json([
                'success' => false,
                'message' => 'Step not found',
            ], 404);
        }
        
        $percentages = $this->calculator->getApproverPercentages($step);

        return response()->json([
            'success' => true,
            'data' => $percentages,
        ]);
    }
}
