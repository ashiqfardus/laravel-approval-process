<?php

namespace AshiqFardus\ApprovalProcess\Http\Controllers\Web;

use AshiqFardus\ApprovalProcess\Http\Controllers\Controller;
use AshiqFardus\ApprovalProcess\Models\Workflow;
use AshiqFardus\ApprovalProcess\Models\CustomReport;
use AshiqFardus\ApprovalProcess\Models\ApprovableEntity;
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
        $approvableModels = $this->getApprovableEntities();
        $availableRoles = config('approval-process.ui.available_roles', []);

        return view('approval-process::admin.workflows.create', compact('approvableModels', 'availableRoles'));
    }

    /**
     * Show edit workflow form.
     */
    public function editWorkflow(int $workflow): View
    {
        $workflow = Workflow::with('steps.approvers')->findOrFail($workflow);
        $approvableModels = $this->discoverModelsAndTables();
        $availableRoles = config('approval-process.ui.available_roles', []);

        return view('approval-process::admin.workflows.edit', compact('workflow', 'approvableModels', 'availableRoles'));
    }

    /**
     * Show workflow details.
     */
    public function viewWorkflow(int $workflow): View
    {
        $workflow = Workflow::with(['steps.approvers'])->findOrFail($workflow);

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

    /**
     * Show approvable entities management.
     */
    public function approvableEntities(): View
    {
        $entities = ApprovableEntity::where('is_active', true)->orderBy('label')->paginate(15);
        $connections = $this->getAvailableConnections();

        return view('approval-process::admin.entities.index', compact('entities', 'connections'));
    }

    /**
     * Show create entity form.
     */
    public function createEntity(): View
    {
        $connections = $this->getAvailableConnections();
        $discoveredModels = $this->discoverModels();
        $discoveredTables = $this->discoverTables();

        return view('approval-process::admin.entities.create', compact('connections', 'discoveredModels', 'discoveredTables'));
    }

    /**
     * Store new entity.
     */
    public function storeEntity(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:model,table',
            'name' => 'required|string',
            'label' => 'required|string',
            'connection' => 'nullable|string',
            'description' => 'nullable|string',
        ]);

        ApprovableEntity::create($validated);

        return redirect()->route('approval-process.entities.index')
            ->with('success', 'Entity added successfully!');
    }

    /**
     * Delete entity.
     */
    public function destroyEntity(int $entity)
    {
        ApprovableEntity::findOrFail($entity)->delete();

        return redirect()->route('approval-process.entities.index')
            ->with('success', 'Entity removed successfully!');
    }

    /**
     * Get approvable entities from database.
     */
    private function getApprovableEntities(): array
    {
        $entities = ApprovableEntity::where('is_active', true)
            ->orderBy('label')
            ->get();

        $result = [];
        foreach ($entities as $entity) {
            $result[$entity->full_identifier] = $entity->label . 
                ($entity->connection ? " ({$entity->connection})" : '');
        }

        return $result;
    }

    /**
     * Get available database connections.
     */
    private function getAvailableConnections(): array
    {
        $connections = config('database.connections', []);
        $result = [];
        
        foreach ($connections as $name => $config) {
            if (isset($config['driver'])) {
                $result[$name] = ucfirst($name) . " ({$config['driver']})";
            }
        }
        
        return $result;
    }

    /**
     * Discover models from app/Models.
     */
    private function discoverModels(): array
    {
        $models = [];
        $modelsPath = app_path('Models');
        
        if (file_exists($modelsPath)) {
            $files = glob($modelsPath . '/*.php');
            foreach ($files as $file) {
                $className = 'App\\Models\\' . basename($file, '.php');
                if (class_exists($className)) {
                    $models[$className] = class_basename($className);
                }
            }
        }
        
        asort($models);
        return $models;
    }

    /**
     * Discover tables from database connections.
     */
    private function discoverTables(string $connection = null): array
    {
        $tables = [];
        
        try {
            $db = $connection ? \DB::connection($connection) : \DB::connection();
            $tableList = $db->select('SHOW TABLES');
            $dbName = $db->getDatabaseName();
            $tableKey = 'Tables_in_' . $dbName;
            
            foreach ($tableList as $table) {
                $tableName = $table->$tableKey;
                if (!$this->isSystemTable($tableName)) {
                    $tables[] = $tableName;
                }
            }
        } catch (\Exception $e) {
            // Silently fail
        }
        
        sort($tables);
        return $tables;
    }

    /**
     * Check if table is a system/package table.
     */
    private function isSystemTable(string $tableName): bool
    {
        $systemTables = [
            'migrations',
            'password_reset_tokens',
            'password_resets',
            'personal_access_tokens',
            'failed_jobs',
            'jobs',
            'job_batches',
            'cache',
            'cache_locks',
            'sessions',
            // Package tables
            'approval_workflows',
            'approval_steps',
            'approval_requests',
            'approval_actions',
            'approval_approvers',
            'approval_delegations',
            'approval_notifications',
            'approval_attachments',
            'approval_document_templates',
            'approval_signatures',
            'approval_workflow_conditions',
            'approval_parallel_groups',
            'approval_parallel_execution_states',
            'approval_dynamic_step_modifications',
            'approval_workflow_modification_rules',
            'approval_custom_reports',
            'approval_report_executions',
        ];

        return in_array($tableName, $systemTables) || str_starts_with($tableName, 'approval_');
    }
}
