<?php

namespace AshiqFardus\ApprovalProcess\Http\Controllers\Admin;

use AshiqFardus\ApprovalProcess\Http\Controllers\Controller;
use AshiqFardus\ApprovalProcess\Models\Workflow;
use AshiqFardus\ApprovalProcess\Models\ApprovalStep;
use AshiqFardus\ApprovalProcess\Models\Approver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WorkflowAdminController extends Controller
{
    /**
     * Show workflow management page
     */
    public function index()
    {
        $workflows = Workflow::with(['steps.approvers'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('approval-process::admin.workflows.index', compact('workflows'));
    }

    /**
     * Show create workflow form
     */
    public function create()
    {
        $availableModels = $this->getApprovableModels();
        return view('approval-process::admin.workflows.create', compact('availableModels'));
    }

    /**
     * Store new workflow
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'model_type' => 'required|string',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $workflow = Workflow::create($validated);

        return redirect()
            ->route('approval-admin.workflows.edit', $workflow)
            ->with('success', 'Workflow created! Now add approval levels.');
    }

    /**
     * Show edit workflow form
     */
    public function edit(Workflow $workflow)
    {
        $workflow->load(['steps.approvers']);
        $users = \App\Models\User::select('id', 'name', 'email')->get();
        $roles = $this->getAvailableRoles();

        return view('approval-process::admin.workflows.edit', compact('workflow', 'users', 'roles'));
    }

    /**
     * Update workflow
     */
    public function update(Request $request, Workflow $workflow)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $workflow->update($validated);

        return redirect()
            ->route('approval-admin.workflows.edit', $workflow)
            ->with('success', 'Workflow updated successfully!');
    }

    /**
     * Delete workflow
     */
    public function destroy(Workflow $workflow)
    {
        if ($workflow->requests()->whereIn('status', ['submitted', 'pending'])->exists()) {
            return back()->with('error', 'Cannot delete workflow with pending approvals!');
        }

        $workflow->delete();

        return redirect()
            ->route('approval-admin.workflows.index')
            ->with('success', 'Workflow deleted successfully!');
    }

    /**
     * Toggle workflow active status
     */
    public function toggleStatus(Workflow $workflow)
    {
        $workflow->update(['is_active' => !$workflow->is_active]);
        return back()->with('success', 'Workflow status updated!');
    }

    /**
     * Add approval level/step
     */
    public function addStep(Request $request, Workflow $workflow)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sequence' => 'required|integer|min:1',
            'approval_type' => 'required|in:serial,parallel,any_one',
            'sla_hours' => 'nullable|integer|min:1',
            'condition_field' => 'nullable|string',
            'condition_operator' => 'nullable|in:=,!=,>,>=,<,<=',
            'condition_value' => 'nullable',
        ]);

        $conditionConfig = null;
        if ($request->filled('condition_field')) {
            $conditionConfig = [
                'field' => $request->condition_field,
                'operator' => $request->condition_operator,
                'value' => $request->condition_value,
            ];
        }

        ApprovalStep::create([
            'workflow_id' => $workflow->id,
            'name' => $validated['name'],
            'sequence' => $validated['sequence'],
            'approval_type' => $validated['approval_type'],
            'sla_hours' => $validated['sla_hours'],
            'condition_config' => $conditionConfig,
        ]);

        return back()->with('success', 'Approval level added!');
    }

    /**
     * Update approval step
     */
    public function updateStep(Request $request, ApprovalStep $step)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sequence' => 'required|integer|min:1',
            'approval_type' => 'required|in:serial,parallel,any_one',
            'sla_hours' => 'nullable|integer|min:1',
            'condition_field' => 'nullable|string',
            'condition_operator' => 'nullable|in:=,!=,>,>=,<,<=',
            'condition_value' => 'nullable',
        ]);

        $conditionConfig = null;
        if ($request->filled('condition_field')) {
            $conditionConfig = [
                'field' => $request->condition_field,
                'operator' => $request->condition_operator,
                'value' => $request->condition_value,
            ];
        }

        $step->update([
            'name' => $validated['name'],
            'sequence' => $validated['sequence'],
            'approval_type' => $validated['approval_type'],
            'sla_hours' => $validated['sla_hours'],
            'condition_config' => $conditionConfig,
        ]);

        return back()->with('success', 'Level updated!');
    }

    /**
     * Delete approval step
     */
    public function deleteStep(ApprovalStep $step)
    {
        $activeRequests = DB::table('approval_requests')
            ->where('current_step_id', $step->id)
            ->whereIn('status', ['submitted', 'pending'])
            ->exists();

        if ($activeRequests) {
            return back()->with('error', 'Cannot delete step with active approvals!');
        }

        $step->delete();
        return back()->with('success', 'Level deleted!');
    }

    /**
     * Add approver to step
     */
    public function addApprover(Request $request, ApprovalStep $step)
    {
        $validated = $request->validate([
            'approver_type' => 'required|in:user,role,manager,department_head,position,custom',
            'user_id' => 'required_if:approver_type,user|nullable|exists:users,id',
            'approver_id' => 'required_unless:approver_type,user,manager|nullable|string',
        ]);

        Approver::create([
            'approval_step_id' => $step->id,
            'approver_type' => $validated['approver_type'],
            'user_id' => $validated['user_id'] ?? null,
            'approver_id' => $validated['approver_id'] ?? null,
        ]);

        return back()->with('success', 'Approver added!');
    }

    /**
     * Remove approver
     */
    public function removeApprover(Approver $approver)
    {
        $approver->delete();
        return back()->with('success', 'Approver removed!');
    }

    /**
     * Reorder steps
     */
    public function reorderSteps(Request $request, Workflow $workflow)
    {
        $validated = $request->validate([
            'steps' => 'required|array',
            'steps.*.id' => 'required|exists:approval_steps,id',
            'steps.*.sequence' => 'required|integer|min:1',
        ]);

        foreach ($validated['steps'] as $stepData) {
            ApprovalStep::where('id', $stepData['id'])
                ->update(['sequence' => $stepData['sequence']]);
        }

        return back()->with('success', 'Steps reordered!');
    }

    /**
     * Clone workflow
     */
    public function clone(Workflow $workflow)
    {
        $newWorkflow = $workflow->replicate();
        $newWorkflow->name = $workflow->name . ' (Copy)';
        $newWorkflow->is_active = false;
        $newWorkflow->save();

        foreach ($workflow->steps as $step) {
            $newStep = $step->replicate();
            $newStep->workflow_id = $newWorkflow->id;
            $newStep->save();

            foreach ($step->approvers as $approver) {
                $newApprover = $approver->replicate();
                $newApprover->approval_step_id = $newStep->id;
                $newApprover->save();
            }
        }

        return redirect()
            ->route('approval-admin.workflows.edit', $newWorkflow)
            ->with('success', 'Workflow cloned!');
    }

    /**
     * Get available models that use Approvable trait
     */
    protected function getApprovableModels(): array
    {
        return config('approval-process.approvable_models', [
            'App\Models\Offer' => 'Offer',
            'App\Models\PurchaseOrder' => 'Purchase Order',
            'App\Models\ExpenseClaim' => 'Expense Claim',
            'App\Models\LeaveRequest' => 'Leave Request',
        ]);
    }

    /**
     * Get available roles
     */
    protected function getAvailableRoles(): array
    {
        if (class_exists(\Spatie\Permission\Models\Role::class)) {
            return \Spatie\Permission\Models\Role::pluck('name', 'name')->toArray();
        }

        return config('approval-process.available_roles', [
            'manager' => 'Manager',
            'finance' => 'Finance',
            'director' => 'Director',
            'hr' => 'HR',
        ]);
    }
}
