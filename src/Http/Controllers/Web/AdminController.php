<?php

namespace AshiqFardus\ApprovalProcess\Http\Controllers\Web;

use AshiqFardus\ApprovalProcess\Http\Controllers\Controller;
use AshiqFardus\ApprovalProcess\Models\Workflow;
use AshiqFardus\ApprovalProcess\Models\CustomReport;
use AshiqFardus\ApprovalProcess\Services\AnalyticsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminController extends Controller
{
    protected AnalyticsService $analytics;

    public function __construct(AnalyticsService $analytics)
    {
        $this->analytics = $analytics;
    }

    /**
     * Show workflows list.
     */
    public function workflows(): View
    {
        $workflows = Workflow::with('steps')->paginate(15);

        return view('approval-process::admin.workflows.index', compact('workflows'));
    }

    /**
     * Show create workflow form.
     */
    public function createWorkflow(): View
    {
        $approvableModels = config('approval-process.approvable_models', []);
        $availableRoles = config('approval-process.available_roles', []);

        return view('approval-process::admin.workflows.create', compact('approvableModels', 'availableRoles'));
    }

    /**
     * Show edit workflow form.
     */
    public function editWorkflow(int $workflow): View
    {
        $workflow = Workflow::with('steps.approvers')->findOrFail($workflow);
        $approvableModels = config('approval-process.approvable_models', []);
        $availableRoles = config('approval-process.available_roles', []);

        return view('approval-process::admin.workflows.edit', compact('workflow', 'approvableModels', 'availableRoles'));
    }

    /**
     * Show workflow details.
     */
    public function viewWorkflow(int $workflow): View
    {
        $workflow = Workflow::with(['steps.approvers', 'conditions', 'parallelGroups'])->findOrFail($workflow);

        return view('approval-process::admin.workflows.view', compact('workflow'));
    }

    /**
     * Show reports list.
     */
    public function reports(): View
    {
        $reports = CustomReport::with('createdBy')->paginate(15);

        return view('approval-process::admin.reports.index', compact('reports'));
    }

    /**
     * Show report view.
     */
    public function viewReport(int $report): View
    {
        $report = CustomReport::with(['createdBy', 'executions'])->findOrFail($report);

        return view('approval-process::admin.reports.view', compact('report'));
    }

    /**
     * Show analytics dashboard.
     */
    public function analytics(): View
    {
        $stats = $this->analytics->getDashboardStats();
        $workflows = Workflow::where('is_active', true)->get();

        return view('approval-process::admin.analytics.index', compact('stats', 'workflows'));
    }

    /**
     * Show workflow analytics.
     */
    public function workflowAnalytics(): View
    {
        $workflows = Workflow::where('is_active', true)->get();

        return view('approval-process::admin.analytics.workflows', compact('workflows'));
    }

    /**
     * Show user analytics.
     */
    public function userAnalytics(): View
    {
        return view('approval-process::admin.analytics.users');
    }

    /**
     * Show settings page.
     */
    public function settings(): View
    {
        $config = config('approval-process');

        return view('approval-process::admin.settings', compact('config'));
    }
}
