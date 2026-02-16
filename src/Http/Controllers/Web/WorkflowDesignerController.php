<?php

namespace AshiqFardus\ApprovalProcess\Http\Controllers\Web;

use AshiqFardus\ApprovalProcess\Http\Controllers\Controller;
use AshiqFardus\ApprovalProcess\Models\Workflow;
use AshiqFardus\ApprovalProcess\Models\ApprovalStep;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;

class WorkflowDesignerController extends Controller
{
    /**
     * Show workflow designer.
     */
    public function show(int $workflow): View
    {
        $workflow = Workflow::with([
            'steps.approvers',
            'conditions.fromStep',
            'conditions.toStep',
            'parallelGroups.steps',
        ])->findOrFail($workflow);

        return view('approval-process::designer.index', compact('workflow'));
    }

    /**
     * Save workflow design.
     */
    public function save(int $workflow, Request $request): JsonResponse
    {
        $workflow = Workflow::findOrFail($workflow);
        
        $designData = $request->input('design');

        // Save workflow configuration
        $workflow->update([
            'name' => $designData['name'] ?? $workflow->name,
            'description' => $designData['description'] ?? $workflow->description,
        ]);

        // Save steps
        if (isset($designData['steps'])) {
            $this->saveSteps($workflow, $designData['steps']);
        }

        // Save conditions
        if (isset($designData['conditions'])) {
            $this->saveConditions($workflow, $designData['conditions']);
        }

        // Save parallel groups
        if (isset($designData['parallel_groups'])) {
            $this->saveParallelGroups($workflow, $designData['parallel_groups']);
        }

        return response()->json([
            'message' => 'Workflow saved successfully',
            'workflow' => $workflow->fresh(),
        ]);
    }

    /**
     * Export workflow design.
     */
    public function export(int $workflow): JsonResponse
    {
        $workflow = Workflow::with([
            'steps.approvers',
        ])->findOrFail($workflow);

        $export = [
            'workflow' => $workflow->toArray(),
            'steps' => $workflow->steps->toArray(),
            'exported_at' => now()->toIso8601String(),
            'version' => '1.0',
        ];

        return response()->json($export);
    }

    /**
     * Import workflow design.
     */
    public function import(Request $request): JsonResponse
    {
        $data = $request->input('workflow_data');

        if (!$data) {
            return response()->json(['message' => 'No workflow data provided'], 422);
        }

        try {
            // Create workflow
            $workflow = Workflow::create([
                'name' => $data['workflow']['name'] . ' (Imported)',
                'model_type' => $data['workflow']['model_type'],
                'description' => $data['workflow']['description'] ?? null,
                'is_active' => false, // Imported workflows start inactive
            ]);

            // Import steps
            if (isset($data['steps'])) {
                foreach ($data['steps'] as $stepData) {
                    ApprovalStep::create(array_merge($stepData, [
                        'workflow_id' => $workflow->id,
                    ]));
                }
            }

            return response()->json([
                'message' => 'Workflow imported successfully',
                'workflow' => $workflow,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Import failed: ' . $e->getMessage()
            ], 422);
        }
    }

    /**
     * Save steps from designer.
     */
    protected function saveSteps(Workflow $workflow, array $steps): void
    {
        foreach ($steps as $stepData) {
            if (isset($stepData['id'])) {
                // Update existing step
                $step = ApprovalStep::find($stepData['id']);
                if ($step) {
                    $step->update($stepData);
                }
            } else {
                // Create new step
                ApprovalStep::create(array_merge($stepData, [
                    'workflow_id' => $workflow->id,
                ]));
            }
        }
    }

    /**
     * Save conditions from designer.
     */
    protected function saveConditions(Workflow $workflow, array $conditions): void
    {
        // Implementation for saving conditions
        // This would integrate with WorkflowCondition model
    }

    /**
     * Save parallel groups from designer.
     */
    protected function saveParallelGroups(Workflow $workflow, array $groups): void
    {
        // Implementation for saving parallel groups
        // This would integrate with ParallelStepGroup model
    }
}
