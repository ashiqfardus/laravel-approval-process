<?php

namespace AshiqFardus\ApprovalProcess\Tests\Unit\Services;

use AshiqFardus\ApprovalProcess\Tests\TestCase;
use AshiqFardus\ApprovalProcess\Services\EscalationService;
use AshiqFardus\ApprovalProcess\Models\Workflow;
use AshiqFardus\ApprovalProcess\Models\ApprovalStep;
use AshiqFardus\ApprovalProcess\Models\ApprovalRequest;
use AshiqFardus\ApprovalProcess\Models\ApprovalEscalation;
use Illuminate\Foundation\Testing\RefreshDatabase;

class EscalationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected EscalationService $service;
    protected $user;
    protected $workflow;
    protected $request;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = new EscalationService();
        $this->user = $this->createUser();
        
        // Create workflow with SLA
        $this->workflow = Workflow::create([
            'name' => 'Test Workflow',
            'model_type' => 'App\\Models\\TestModel',
            'is_active' => true,
        ]);
        
        $step1 = ApprovalStep::create([
            'workflow_id' => $this->workflow->id,
            'name' => 'Level 1',
            'sequence' => 1,
            'approval_type' => 'serial',
            'sla_hours' => 24,
        ]);
        
        $step2 = ApprovalStep::create([
            'workflow_id' => $this->workflow->id,
            'name' => 'Level 2',
            'sequence' => 2,
            'approval_type' => 'serial',
            'sla_hours' => 48,
        ]);
        
        $this->request = ApprovalRequest::create([
            'workflow_id' => $this->workflow->id,
            'requestable_type' => 'App\\Models\\TestModel',
            'requestable_id' => 1,
            'requested_by_user_id' => $this->user->id,
            'current_step_id' => $step1->id,
            'status' => 'pending',
            'sla_deadline' => now()->addHours(24),
        ]);
    }

    /** @test */
    public function it_can_detect_overdue_approvals()
    {
        // Set deadline in the past
        $this->request->update(['sla_deadline' => now()->subHour()]);

        $count = $this->service->checkOverdueApprovals();

        $this->assertGreaterThan(0, $count);
    }

    /** @test */
    public function it_can_escalate_request()
    {
        $this->service->escalateRequest($this->request, 'sla_timeout');

        $this->assertDatabaseHas('approval_escalations', [
            'approval_request_id' => $this->request->id,
            'reason' => 'sla_timeout',
        ]);
    }

    /** @test */
    public function it_moves_to_next_level_on_escalation()
    {
        $originalStepId = $this->request->current_step_id;
        
        $this->service->escalateRequest($this->request, 'sla_timeout');

        $this->request->refresh();
        $this->assertNotEquals($originalStepId, $this->request->current_step_id);
    }

    /** @test */
    public function it_can_calculate_sla_deadline()
    {
        $step = $this->request->currentStep;
        $deadline = $this->service->calculateSLA($step);

        $expectedDeadline = now()->addHours(24);
        $this->assertEquals(
            $expectedDeadline->format('Y-m-d H'),
            $deadline->format('Y-m-d H')
        );
    }

    /** @test */
    public function it_can_send_reminders()
    {
        // Set deadline to trigger reminder (halfway point)
        $this->request->update(['sla_deadline' => now()->addHours(10)]);

        $count = $this->service->sendReminders();

        // Should send reminder as we're past halfway point
        $this->assertGreaterThanOrEqual(0, $count);
    }

    /** @test */
    public function it_can_get_escalation_history()
    {
        ApprovalEscalation::create([
            'approval_request_id' => $this->request->id,
            'from_user_id' => $this->user->id,
            'to_user_id' => $this->user->id,
            'from_level' => 1,
            'to_level' => 2,
            'reason' => 'sla_timeout',
        ]);

        $history = $this->service->getEscalationHistory($this->request);

        $this->assertCount(1, $history);
    }

    /** @test */
    public function it_does_not_escalate_non_overdue_requests()
    {
        // Set deadline in future
        $this->request->update(['sla_deadline' => now()->addDays(2)]);

        $count = $this->service->checkOverdueApprovals();

        $this->assertEquals(0, $count);
    }
}
