<?php

namespace AshiqFardus\ApprovalProcess\Tests\Feature;

use AshiqFardus\ApprovalProcess\Tests\TestCase;
use AshiqFardus\ApprovalProcess\Services\ApprovalEngine;
use AshiqFardus\ApprovalProcess\Models\Workflow;
use AshiqFardus\ApprovalProcess\Models\ApprovalStep;
use AshiqFardus\ApprovalProcess\Models\ApprovalRequest;
use AshiqFardus\ApprovalProcess\Models\ApprovalApprover;
use AshiqFardus\ApprovalProcess\Models\ApprovalAction;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ApprovalWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected ApprovalEngine $engine;
    protected $creator;
    protected $approver1;
    protected $approver2;
    protected $workflow;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->engine = new ApprovalEngine();
        $this->creator = $this->createUser(['email' => 'creator@example.com']);
        $this->approver1 = $this->createUser(['email' => 'approver1@example.com']);
        $this->approver2 = $this->createUser(['email' => 'approver2@example.com']);
        
        // Create a 2-level workflow
        $this->workflow = Workflow::create([
            'name' => 'Purchase Order Approval',
            'model_type' => 'App\\Models\\PurchaseOrder',
            'is_active' => true,
        ]);
        
        $step1 = ApprovalStep::create([
            'workflow_id' => $this->workflow->id,
            'name' => 'Manager Approval',
            'sequence' => 1,
            'approval_type' => 'serial',
            'level_alias' => 'Checked By',
            'sla_hours' => 24,
        ]);
        
        $step2 = ApprovalStep::create([
            'workflow_id' => $this->workflow->id,
            'name' => 'Director Approval',
            'sequence' => 2,
            'approval_type' => 'serial',
            'level_alias' => 'Approved By',
            'sla_hours' => 48,
        ]);
        
        ApprovalApprover::create([
            'approval_step_id' => $step1->id,
            'user_id' => $this->approver1->id,
        ]);
        
        ApprovalApprover::create([
            'approval_step_id' => $step2->id,
            'user_id' => $this->approver2->id,
        ]);
    }

    /** @test */
    public function it_can_submit_approval_request()
    {
        $model = new \stdClass();
        $model->id = 1;
        $model->amount = 5000;

        $request = $this->engine->submitRequest($model, $this->creator->id);

        $this->assertInstanceOf(ApprovalRequest::class, $request);
        $this->assertEquals('submitted', $request->status);
        $this->assertNotNull($request->current_step_id);
    }

    /** @test */
    public function it_can_complete_full_approval_workflow()
    {
        $model = new \stdClass();
        $model->id = 1;

        // Submit request
        $request = $this->engine->submitRequest($model, $this->creator->id);

        // Level 1 approval
        $this->engine->approve($request, $this->approver1->id, 'Approved by manager');
        $request->refresh();

        // Should move to level 2
        $this->assertEquals(2, $request->currentStep->sequence);

        // Level 2 approval
        $this->engine->approve($request, $this->approver2->id, 'Approved by director');
        $request->refresh();

        // Should be fully approved
        $this->assertEquals('approved', $request->status);
        $this->assertNotNull($request->completed_at);
    }

    /** @test */
    public function it_can_reject_approval_request()
    {
        $model = new \stdClass();
        $model->id = 1;

        $request = $this->engine->submitRequest($model, $this->creator->id);

        $this->engine->reject($request, $this->approver1->id, 'Budget exceeded', 'Rejected');

        $request->refresh();

        $this->assertEquals('rejected', $request->status);
        $this->assertEquals('Budget exceeded', $request->rejection_reason);
        $this->assertNotNull($request->rejected_at);
    }

    /** @test */
    public function it_can_send_back_to_previous_step()
    {
        $model = new \stdClass();
        $model->id = 1;

        $request = $this->engine->submitRequest($model, $this->creator->id);

        // Approve level 1
        $this->engine->approve($request, $this->approver1->id);
        $request->refresh();

        $currentStepId = $request->current_step_id;

        // Send back from level 2
        $this->engine->sendBack($request, $this->approver2->id, 'Need more details');
        $request->refresh();

        // Should be back at level 1
        $this->assertNotEquals($currentStepId, $request->current_step_id);
        $this->assertEquals(1, $request->currentStep->sequence);
    }

    /** @test */
    public function it_records_all_approval_actions()
    {
        $model = new \stdClass();
        $model->id = 1;

        $request = $this->engine->submitRequest($model, $this->creator->id);
        $this->engine->approve($request, $this->approver1->id, 'Looks good');
        $this->engine->approve($request, $this->approver2->id, 'Final approval');

        $actions = ApprovalAction::where('approval_request_id', $request->id)->get();

        $this->assertCount(2, $actions);
        $this->assertEquals('approved', $actions->first()->action);
    }

    /** @test */
    public function it_can_calculate_approval_progress()
    {
        $model = new \stdClass();
        $model->id = 1;

        $request = $this->engine->submitRequest($model, $this->creator->id);

        // Initial progress
        $progress = $this->engine->calculateApprovalProgress($request);
        $this->assertEquals(0, $progress['completed_steps']);
        $this->assertEquals(0, $progress['progress_percentage']);

        // After first approval
        $this->engine->approve($request, $this->approver1->id);
        $request->refresh();

        $progress = $this->engine->calculateApprovalProgress($request);
        $this->assertEquals(1, $progress['completed_steps']);
        $this->assertEquals(50, $progress['progress_percentage']);

        // After second approval
        $this->engine->approve($request, $this->approver2->id);
        $request->refresh();

        $progress = $this->engine->calculateApprovalProgress($request);
        $this->assertEquals(2, $progress['completed_steps']);
        $this->assertEquals(100, $progress['progress_percentage']);
    }

    /** @test */
    public function it_can_handle_higher_level_creator_with_auto_approval()
    {
        // Make approver2 (Level 2) the creator
        ApprovalApprover::create([
            'approval_step_id' => $this->workflow->activeSteps()->where('sequence', 2)->first()->id,
            'user_id' => $this->creator->id,
        ]);

        $model = new \stdClass();
        $model->id = 1;

        $request = $this->engine->createWithAutoApproval($model, $this->creator->id);

        // Should auto-approve Level 1
        $this->assertEquals(2, $request->creator_level);
        $this->assertTrue($request->skip_previous_levels);

        // Should start at Level 2
        $this->assertEquals(2, $request->currentStep->sequence);

        // Check that Level 1 was auto-approved
        $level1Action = ApprovalAction::where('approval_request_id', $request->id)
            ->where('action', 'approved')
            ->first();

        $this->assertNotNull($level1Action);
        $this->assertStringContainsString('Auto-approved', $level1Action->remarks);
    }
}
