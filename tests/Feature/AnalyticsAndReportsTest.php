<?php

namespace AshiqFardus\ApprovalProcess\Tests\Feature;

use AshiqFardus\ApprovalProcess\Tests\TestCase;
use AshiqFardus\ApprovalProcess\Models\Workflow;
use AshiqFardus\ApprovalProcess\Models\ApprovalStep;
use AshiqFardus\ApprovalProcess\Models\ApprovalRequest;
use AshiqFardus\ApprovalProcess\Models\CustomReport;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AnalyticsAndReportsTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $workflow;
    protected $step;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->withoutMiddleware();
        
        $this->user = $this->createUser();
        
        $this->workflow = Workflow::create([
            'name' => 'Test Workflow',
            'model_type' => 'App\\Models\\Document',
            'is_active' => true,
        ]);

        $this->step = ApprovalStep::create([
            'workflow_id' => $this->workflow->id,
            'name' => 'Manager Approval',
            'sequence' => 1,
            'approval_type' => 'serial',
        ]);
    }

    /** @test */
    public function it_can_get_dashboard_stats()
    {
        ApprovalRequest::create([
            'workflow_id' => $this->workflow->id,
            'requestable_type' => 'App\\Models\\Document',
            'requestable_id' => 1,
            'requested_by_user_id' => $this->user->id,
            'status' => 'approved',
            'current_step_id' => $this->step->id,
        ]);

        $response = $this->getJson('/api/approval-process/dashboard/stats');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'total_requests',
            'pending_requests',
            'approved_requests',
            'rejected_requests',
        ]);
    }

    /** @test */
    public function it_can_get_workflow_metrics()
    {
        $response = $this->getJson("/api/approval-process/analytics/workflows/{$this->workflow->id}/metrics");

        $response->assertStatus(200);
    }

    /** @test */
    public function it_can_get_user_metrics()
    {
        $response = $this->getJson("/api/approval-process/analytics/users/{$this->user->id}/metrics");

        $response->assertStatus(200);
    }

    /** @test */
    public function it_can_detect_bottlenecks()
    {
        $response = $this->getJson('/api/approval-process/analytics/bottlenecks');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'date',
            'bottlenecks',
            'count',
        ]);
    }

    /** @test */
    public function it_can_get_trend_data()
    {
        $response = $this->getJson('/api/approval-process/analytics/trends?metric_type=request_count&period=daily');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'metric_type',
            'period',
            'data',
        ]);
    }

    /** @test */
    public function it_can_get_top_performers()
    {
        $response = $this->getJson('/api/approval-process/analytics/top-performers?limit=5');

        $response->assertStatus(200);
        $response->assertJsonIsArray();
    }

    /** @test */
    public function it_can_compare_workflows()
    {
        $workflow2 = Workflow::create([
            'name' => 'Test Workflow 2',
            'model_type' => 'App\\Models\\Test',
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/approval-process/analytics/compare-workflows', [
            'workflow_ids' => [$this->workflow->id, $workflow2->id],
            'start_date' => now()->subDays(30)->toDateString(),
            'end_date' => now()->toDateString(),
        ]);

        $response->assertStatus(200);
        $response->assertJsonCount(2);
    }

    /** @test */
    public function it_can_create_a_custom_report()
    {
        $response = $this->postJson('/api/approval-process/reports', [
            'name' => 'Test Report',
            'code' => 'test_report',
            'description' => 'A test report',
            'report_type' => 'summary',
            'filters' => [],
            'columns' => ['id', 'status', 'created_at'],
            'is_public' => true,
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure(['message', 'report']);
        
        $this->assertDatabaseHas('custom_reports', [
            'code' => 'test_report',
        ]);
    }

    /** @test */
    public function it_can_get_all_reports()
    {
        CustomReport::create([
            'name' => 'Report 1',
            'code' => 'report1',
            'report_type' => 'summary',
        ]);

        $response = $this->getJson('/api/approval-process/reports');

        $response->assertStatus(200);
        $response->assertJsonCount(1);
    }

    /** @test */
    public function it_can_execute_a_report()
    {
        $report = CustomReport::create([
            'name' => 'Test Report',
            'code' => 'test_report',
            'report_type' => 'summary',
            'filters' => [],
            'columns' => ['id', 'status'],
        ]);

        $response = $this->postJson("/api/approval-process/reports/{$report->id}/execute", [
            'format' => 'json',
            'parameters' => [],
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure(['message', 'execution']);
    }

    /** @test */
    public function it_can_get_report_executions()
    {
        $report = CustomReport::create([
            'name' => 'Test Report',
            'code' => 'test_report',
            'report_type' => 'summary',
        ]);

        $response = $this->getJson("/api/approval-process/reports/{$report->id}/executions");

        $response->assertStatus(200);
    }

    /** @test */
    public function it_can_generate_audit_report()
    {
        $response = $this->getJson('/api/approval-process/reports-audit?start_date=' . now()->subDays(7)->toDateString());

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'start_date',
            'end_date',
            'total_actions',
            'data',
        ]);
    }

    /** @test */
    public function it_can_get_report_types()
    {
        $response = $this->getJson('/api/approval-process/reports-types');

        $response->assertStatus(200);
        $response->assertJsonStructure(['report_types']);
    }
}
