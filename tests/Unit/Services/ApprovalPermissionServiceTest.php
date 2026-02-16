<?php

namespace AshiqFardus\ApprovalProcess\Tests\Unit\Services;

use AshiqFardus\ApprovalProcess\Tests\TestCase;
use AshiqFardus\ApprovalProcess\Services\ApprovalPermissionService;
use AshiqFardus\ApprovalProcess\Models\Workflow;
use AshiqFardus\ApprovalProcess\Models\ApprovalStep;
use AshiqFardus\ApprovalProcess\Models\ApprovalApprover;
use AshiqFardus\ApprovalProcess\Models\ApprovalDelegation;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ApprovalPermissionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ApprovalPermissionService $service;
    protected $user;
    protected $workflow;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = new ApprovalPermissionService();
        $this->user = $this->createUser();
        
        // Create a test workflow
        $this->workflow = Workflow::create([
            'name' => 'Test Workflow',
            'model_type' => 'App\\Models\\TestModel',
            'is_active' => true,
        ]);
        
        // Create approval steps
        $step1 = ApprovalStep::create([
            'workflow_id' => $this->workflow->id,
            'name' => 'Level 1 Approval',
            'sequence' => 1,
            'approval_type' => 'serial',
        ]);
        
        $step2 = ApprovalStep::create([
            'workflow_id' => $this->workflow->id,
            'name' => 'Level 2 Approval',
            'sequence' => 2,
            'approval_type' => 'serial',
        ]);
    }

    /** @test */
    public function it_can_detect_user_approval_level()
    {
        // Assign user to Level 2
        ApprovalApprover::create([
            'approval_step_id' => $this->workflow->activeSteps()->where('sequence', 2)->first()->id,
            'user_id' => $this->user->id,
        ]);

        $level = $this->service->getUserLevel($this->user->id, 'App\\Models\\TestModel');

        $this->assertEquals(2, $level);
    }

    /** @test */
    public function it_returns_null_for_non_approver()
    {
        $level = $this->service->getUserLevel($this->user->id, 'App\\Models\\TestModel');

        $this->assertNull($level);
    }

    /** @test */
    public function it_can_check_if_user_can_create_document()
    {
        // Assign user to Level 2
        ApprovalApprover::create([
            'approval_step_id' => $this->workflow->activeSteps()->where('sequence', 2)->first()->id,
            'user_id' => $this->user->id,
        ]);

        $canCreate = $this->service->canCreateDocument($this->user->id, 'App\\Models\\TestModel');

        $this->assertTrue($canCreate);
    }

    /** @test */
    public function it_can_get_levels_to_auto_approve()
    {
        $levels = $this->service->getLevelsToAutoApprove(2);

        $this->assertEquals([1], $levels);
    }

    /** @test */
    public function it_can_get_effective_approver_with_delegation()
    {
        $delegate = $this->createUser(['email' => 'delegate@example.com']);
        
        // Create delegation
        ApprovalDelegation::create([
            'delegator_id' => $this->user->id,
            'delegate_id' => $delegate->id,
            'module_type' => 'App\\Models\\TestModel',
            'start_date' => now()->subDay(),
            'end_date' => now()->addDay(),
        ]);

        $effectiveApprover = $this->service->getEffectiveApprover($this->user->id, 'App\\Models\\TestModel');

        $this->assertEquals($delegate->id, $effectiveApprover);
    }

    /** @test */
    public function it_returns_original_user_when_no_delegation()
    {
        $effectiveApprover = $this->service->getEffectiveApprover($this->user->id, 'App\\Models\\TestModel');

        $this->assertEquals($this->user->id, $effectiveApprover);
    }

    /** @test */
    public function it_can_check_if_user_is_approver()
    {
        ApprovalApprover::create([
            'approval_step_id' => $this->workflow->activeSteps()->first()->id,
            'user_id' => $this->user->id,
        ]);

        $isApprover = $this->service->isApprover($this->user->id, 'App\\Models\\TestModel');

        $this->assertTrue($isApprover);
    }

    /** @test */
    public function it_can_get_approvers_at_specific_level()
    {
        $step = $this->workflow->activeSteps()->where('sequence', 1)->first();
        
        ApprovalApprover::create([
            'approval_step_id' => $step->id,
            'user_id' => $this->user->id,
        ]);

        $approvers = $this->service->getApproversAtLevel('App\\Models\\TestModel', 1);

        $this->assertCount(1, $approvers);
        $this->assertEquals($this->user->id, $approvers->first()->user_id);
    }
}
