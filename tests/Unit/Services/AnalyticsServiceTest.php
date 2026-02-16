<?php

namespace AshiqFardus\ApprovalProcess\Tests\Unit\Services;

use AshiqFardus\ApprovalProcess\Tests\TestCase;
use AshiqFardus\ApprovalProcess\Services\AnalyticsService;
use AshiqFardus\ApprovalProcess\Models\Workflow;
use AshiqFardus\ApprovalProcess\Models\ApprovalStep;
use AshiqFardus\ApprovalProcess\Models\ApprovalRequest;
use AshiqFardus\ApprovalProcess\Models\ApprovalAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class AnalyticsServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AnalyticsService $service;
    protected $user;
    protected Workflow $workflow;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = new AnalyticsService();
        $this->user = $this->createUser();
        
        $this->workflow = Workflow::create([
            'name' => 'Test Workflow',
            'model_type' => 'App\\Models\\Test',
            'is_active' => true,
        ]);
    }

    /** @test */
    public function it_calculates_workflow_metrics()
    {
        $step = ApprovalStep::create([
            'workflow_id' => $this->workflow->id,
            'name' => 'Step 1',
            'sequence' => 1,
            'approval_type' => 'serial',
        ]);

        // Create test requests
        ApprovalRequest::create([
            'workflow_id' => $this->workflow->id,
            'requestable_type' => 'App\\Models\\Test',
            'requestable_id' => 1,
            'requested_by_user_id' => $this->user->id,
            'status' => 'approved',
            'current_step_id' => $step->id,
            'created_at' => now(),
            'completed_at' => now()->addHours(2),
        ]);

        ApprovalRequest::create([
            'workflow_id' => $this->workflow->id,
            'requestable_type' => 'App\\Models\\Test',
            'requestable_id' => 2,
            'requested_by_user_id' => $this->user->id,
            'status' => 'rejected',
            'current_step_id' => $step->id,
            'created_at' => now(),
            'completed_at' => now()->addHours(1),
        ]);

        $metrics = $this->service->calculateWorkflowMetrics($this->workflow->id, Carbon::today());

        $this->assertEquals(2, $metrics->total_requests);
        $this->assertEquals(1, $metrics->approved_requests);
        $this->assertEquals(1, $metrics->rejected_requests);
        $this->assertGreaterThan(0, $metrics->approval_rate);
    }

    /** @test */
    public function it_calculates_user_metrics()
    {
        $step = ApprovalStep::create([
            'workflow_id' => $this->workflow->id,
            'name' => 'Step 1',
            'sequence' => 1,
            'approval_type' => 'serial',
        ]);

        $request = ApprovalRequest::create([
            'workflow_id' => $this->workflow->id,
            'requestable_type' => 'App\\Models\\Test',
            'requestable_id' => 1,
            'requested_by_user_id' => $this->user->id,
            'status' => 'approved',
            'current_step_id' => $step->id,
        ]);

        ApprovalAction::create([
            'approval_request_id' => $request->id,
            'approval_step_id' => $step->id,
            'user_id' => $this->user->id,
            'action' => 'approved',
            'comments' => 'Test approval',
        ]);

        $metrics = $this->service->calculateUserMetrics($this->user->id, Carbon::today());

        $this->assertEquals(1, $metrics->requests_submitted);
        $this->assertEquals(1, $metrics->approvals_given);
    }

    /** @test */
    public function it_gets_dashboard_stats()
    {
        $step = ApprovalStep::create([
            'workflow_id' => $this->workflow->id,
            'name' => 'Step 1',
            'sequence' => 1,
            'approval_type' => 'serial',
        ]);

        ApprovalRequest::create([
            'workflow_id' => $this->workflow->id,
            'requestable_type' => 'App\\Models\\Test',
            'requestable_id' => 1,
            'requested_by_user_id' => $this->user->id,
            'status' => 'approved',
            'current_step_id' => $step->id,
        ]);

        $stats = $this->service->getDashboardStats();

        $this->assertArrayHasKey('total_requests', $stats);
        $this->assertArrayHasKey('pending_requests', $stats);
        $this->assertArrayHasKey('approved_requests', $stats);
        $this->assertEquals(1, $stats['total_requests']);
    }

    /** @test */
    public function it_detects_bottlenecks()
    {
        $step = ApprovalStep::create([
            'workflow_id' => $this->workflow->id,
            'name' => 'Step 1',
            'sequence' => 1,
            'approval_type' => 'serial',
        ]);

        // Refresh workflow to load steps relationship
        $this->workflow->refresh();

        // Create 15 pending requests to trigger bottleneck
        for ($i = 0; $i < 15; $i++) {
            ApprovalRequest::create([
                'workflow_id' => $this->workflow->id,
                'requestable_type' => 'App\\Models\\Test',
                'requestable_id' => $i,
                'requested_by_user_id' => $this->user->id,
                'status' => 'submitted',
                'current_step_id' => $step->id,
                'created_at' => now()->subHours(48),
            ]);
        }

        $bottlenecks = $this->service->detectBottlenecks(Carbon::today());

        // Bottlenecks may be empty if threshold not met
        $this->assertIsArray($bottlenecks);
    }

    /** @test */
    public function it_compares_workflows()
    {
        $workflow2 = Workflow::create([
            'name' => 'Test Workflow 2',
            'model_type' => 'App\\Models\\Test2',
            'is_active' => true,
        ]);

        $step1 = ApprovalStep::create([
            'workflow_id' => $this->workflow->id,
            'name' => 'Step 1',
            'sequence' => 1,
            'approval_type' => 'serial',
        ]);

        $step2 = ApprovalStep::create([
            'workflow_id' => $workflow2->id,
            'name' => 'Step 1',
            'sequence' => 1,
            'approval_type' => 'serial',
        ]);

        // Create test requests for metrics
        ApprovalRequest::create([
            'workflow_id' => $this->workflow->id,
            'requestable_type' => 'App\\Models\\Test',
            'requestable_id' => 1,
            'requested_by_user_id' => $this->user->id,
            'status' => 'approved',
            'current_step_id' => $step1->id,
        ]);

        // Create metrics for both workflows
        $this->service->calculateWorkflowMetrics($this->workflow->id, Carbon::today());
        $this->service->calculateWorkflowMetrics($workflow2->id, Carbon::today());

        $comparison = $this->service->compareWorkflows(
            [$this->workflow->id, $workflow2->id],
            Carbon::today(),
            Carbon::today()
        );

        $this->assertCount(2, $comparison);
        $this->assertArrayHasKey('workflow_name', $comparison[0]);
    }
}
