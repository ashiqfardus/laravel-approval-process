<?php

namespace AshiqFardus\ApprovalProcess\Http\Controllers;

use AshiqFardus\ApprovalProcess\Models\Workflow;
use AshiqFardus\ApprovalProcess\Models\ApprovalStep;
use AshiqFardus\ApprovalProcess\Http\Requests\WorkflowRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class WorkflowController extends Controller
{
    /**
     * Display a listing of workflows.
     */
    public function index(): JsonResponse
    {
        $workflows = Workflow::with('steps')
            ->paginate(config('approval-process.ui.items_per_page'));

        return response()->json($workflows);
    }

    /**
     * Store a newly created workflow.
     */
    public function store(WorkflowRequest $request): JsonResponse
    {
        $workflow = Workflow::create($request->validated());

        return response()->json($workflow, 201);
    }

    /**
     * Display the specified workflow.
     */
    public function show(Workflow $workflow): JsonResponse
    {
        $workflow->load('steps.approvers');

        return response()->json($workflow);
    }

    /**
     * Update the specified workflow.
     */
    public function update(WorkflowRequest $request, Workflow $workflow): JsonResponse
    {
        $workflow->update($request->validated());

        return response()->json($workflow);
    }

    /**
     * Delete the specified workflow.
     */
    public function destroy(Workflow $workflow): JsonResponse
    {
        $workflow->delete();

        return response()->json(null, 204);
    }

    /**
     * Clone a workflow.
     */
    public function clone(Workflow $workflow): JsonResponse
    {
        $newName = request()->input('name', $workflow->name . ' - Copy');
        $cloned = $workflow->cloneWorkflow($newName);

        return response()->json($cloned, 201);
    }

    /**
     * Enable a workflow.
     */
    public function enable(Workflow $workflow): JsonResponse
    {
        $workflow->enable();

        return response()->json($workflow);
    }

    /**
     * Disable a workflow.
     */
    public function disable(Workflow $workflow): JsonResponse
    {
        $workflow->disable();

        return response()->json($workflow);
    }

    /**
     * Get all steps for a workflow.
     */
    public function steps($workflow_id): JsonResponse
    {
        $workflow = Workflow::findOrFail($workflow_id);
        
        $steps = $workflow->steps()
            ->with('approvers')
            ->orderBy('sequence')
            ->get();

        return response()->json($steps);
    }

    /**
     * Add a new step to the workflow.
     */
    public function addStep($workflowId): JsonResponse
    {
        $request = request();
        
        // Resolve workflow (route model binding may not work in tests)
        $workflow = Workflow::findOrFail($workflowId);
        
        // Ensure workflow is loaded
        $workflow->refresh();
        
        // Get the highest sequence number
        $maxSequence = $workflow->steps()->max('sequence') ?? 0;
        $sequence = $request->input('sequence', $maxSequence + 1);

        // Validate sequence doesn't conflict
        $existingStep = $workflow->steps()->where('sequence', $sequence)->first();
        if ($existingStep) {
            // Shift existing steps
            $workflow->steps()
                ->where('sequence', '>=', $sequence)
                ->increment('sequence');
        }

        // Ensure workflow exists and has an ID
        if (!$workflow || !$workflow->id) {
            return response()->json(['message' => 'Workflow not found'], 404);
        }
        
        $step = ApprovalStep::create([
            'workflow_id' => $workflow->id,
            'name' => $request->input('name'),
            'description' => $request->input('description'),
            'sequence' => $sequence,
            'approval_type' => $request->input('approval_type', ApprovalStep::APPROVAL_TYPE_SERIAL),
            'level_alias' => $request->input('level_alias'),
            'allow_edit' => $request->input('allow_edit', false),
            'allow_send_back' => $request->input('allow_send_back', true),
            'is_active' => $request->input('is_active', true),
            'sla_hours' => $request->input('sla_hours'),
            'escalation_strategy' => $request->input('escalation_strategy'),
            'allows_delegation' => $request->input('allows_delegation', true),
            'allows_partial_approval' => $request->input('allows_partial_approval', false),
            'condition_config' => $request->input('condition_config'),
        ]);

        $step->load('approvers');
        return response()->json($step, 201);
    }

    /**
     * Update a step in the workflow.
     */
    public function updateStep($workflow_id, $step_id): JsonResponse
    {
        $workflow = Workflow::findOrFail($workflow_id);
        $step = ApprovalStep::findOrFail($step_id);
        
        // Verify step belongs to workflow
        if ($step->workflow_id !== $workflow->id) {
            return response()->json(['message' => 'Step does not belong to this workflow'], 422);
        }

        $request = request();
        $newSequence = $request->input('sequence');

        // Handle sequence change
        if ($newSequence !== null && $newSequence != $step->sequence) {
            $this->reorderStep($workflow, $step, $newSequence);
        }

        $step->update($request->only([
            'name',
            'description',
            'approval_type',
            'level_alias',
            'allow_edit',
            'allow_send_back',
            'is_active',
            'sla_hours',
            'escalation_strategy',
            'allows_delegation',
            'allows_partial_approval',
            'condition_config',
        ]));

        $step->refresh();
        $step->load('approvers');
        return response()->json($step);
    }

    /**
     * Remove a step from the workflow.
     */
    public function removeStep($workflow_id, $step_id): JsonResponse
    {
        $workflow = Workflow::findOrFail($workflow_id);
        $step = ApprovalStep::findOrFail($step_id);
        
        // Verify step belongs to workflow
        if ($step->workflow_id !== $workflow->id) {
            return response()->json(['message' => 'Step does not belong to this workflow'], 422);
        }

        // Check if step has pending approvals
        $hasPendingApprovals = $workflow->requests()
            ->where('current_step_id', $step->id)
            ->whereIn('status', [
                \AshiqFardus\ApprovalProcess\Models\ApprovalRequest::STATUS_SUBMITTED,
                \AshiqFardus\ApprovalProcess\Models\ApprovalRequest::STATUS_IN_REVIEW,
                \AshiqFardus\ApprovalProcess\Models\ApprovalRequest::STATUS_PENDING,
            ])
            ->exists();

        if ($hasPendingApprovals) {
            return response()->json([
                'message' => 'Cannot remove step with pending approval requests. Disable it instead.'
            ], 422);
        }

        $sequence = $step->sequence;
        $step->delete();

        // Reorder remaining steps
        $workflow->steps()
            ->where('sequence', '>', $sequence)
            ->decrement('sequence');

        return response()->json(null, 204);
    }

    /**
     * Reorder steps in the workflow.
     */
    public function reorderSteps($workflow_id): JsonResponse
    {
        $workflow = Workflow::findOrFail($workflow_id);
        
        $request = request();
        $stepIds = $request->input('step_ids', []);

        if (empty($stepIds)) {
            return response()->json(['message' => 'step_ids array is required'], 422);
        }

        // Verify all steps belong to workflow
        $stepCount = $workflow->steps()->whereIn('id', $stepIds)->count();
        if ($stepCount !== count($stepIds)) {
            return response()->json(['message' => 'Some steps do not belong to this workflow'], 422);
        }

        DB::transaction(function () use ($workflow, $stepIds) {
            // First, set all sequences to negative values to avoid unique constraint violations
            foreach ($stepIds as $index => $stepId) {
                ApprovalStep::where('id', $stepId)
                    ->where('workflow_id', $workflow->id)
                    ->update(['sequence' => -($index + 1)]);
            }
            
            // Then, set them to positive values
            foreach ($stepIds as $index => $stepId) {
                ApprovalStep::where('id', $stepId)
                    ->where('workflow_id', $workflow->id)
                    ->update(['sequence' => $index + 1]);
            }
        });

        $steps = $workflow->steps()->orderBy('sequence')->get();

        return response()->json($steps);
    }

    /**
     * Reorder a single step.
     */
    protected function reorderStep(Workflow $workflow, ApprovalStep $step, int $newSequence): void
    {
        $oldSequence = $step->sequence;
        $maxSequence = $workflow->steps()->max('sequence') ?? 0;

        // Validate new sequence
        if ($newSequence < 1 || $newSequence > $maxSequence) {
            throw new \InvalidArgumentException("Sequence must be between 1 and {$maxSequence}");
        }

        DB::transaction(function () use ($workflow, $step, $oldSequence, $newSequence) {
            // Set the moving step to a temporary negative value to avoid conflicts
            $step->update(['sequence' => -1]);
            
            if ($newSequence > $oldSequence) {
                // Moving down: shift steps up
                $workflow->steps()
                    ->where('sequence', '>', $oldSequence)
                    ->where('sequence', '<=', $newSequence)
                    ->decrement('sequence');
            } else {
                // Moving up: shift steps down
                $workflow->steps()
                    ->where('sequence', '>=', $newSequence)
                    ->where('sequence', '<', $oldSequence)
                    ->increment('sequence');
            }

            // Set the final sequence
            $step->update(['sequence' => $newSequence]);
        });
    }
}
