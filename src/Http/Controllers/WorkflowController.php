<?php

namespace AshiqFardus\ApprovalProcess\Http\Controllers;

use AshiqFardus\ApprovalProcess\Models\Workflow;
use AshiqFardus\ApprovalProcess\Http\Requests\WorkflowRequest;
use Illuminate\Http\JsonResponse;

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
}
