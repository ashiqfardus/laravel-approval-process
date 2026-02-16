<?php

namespace AshiqFardus\ApprovalProcess\Tests\Unit\Services;

use AshiqFardus\ApprovalProcess\Tests\TestCase;
use AshiqFardus\ApprovalProcess\Services\ParallelWorkflowManager;
use AshiqFardus\ApprovalProcess\Models\Workflow;
use AshiqFardus\ApprovalProcess\Models\ApprovalStep;
use AshiqFardus\ApprovalProcess\Models\ApprovalRequest;
use AshiqFardus\ApprovalProcess\Models\ParallelStepGroup;
use AshiqFardus\ApprovalProcess\Models\ParallelExecutionState;
use AshiqFardus\ApprovalProcess\Models\ActiveParallelStep;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ParallelWorkflowManagerTest extends TestCase
{
    use RefreshDatabase;

    protected ParallelWorkflowManager $manager;
    protected Workflow $workflow;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->manager = new ParallelWorkflowManager();
        $this->user = $this->createUser();
        
        $this->workflow = Workflow::create([
            'name' => 'Parallel Workflow',
            'model_type' => 'App\\Models\\Test',
            'is_active' => true,
        ]);
    }

    /** @test */
    public function it_creates_a_parallel_group()
    {
        $step1 = ApprovalStep::create([
            'workflow_id' => $this->workflow->id,
            'name' => 'Finance Review',
            'sequence' => 2,
            'approval_type' => 'serial',
        ]);

        $step2 = ApprovalStep::create([
            'workflow_id' => $this->workflow->id,
            'name' => 'IT Review',
            'sequence' => 3,
            'approval_type' => 'serial',
        ]);

        $group = $this->manager->createParallelGroup(
            $this->workflow->id,
            'Finance & IT Review',
            [$step1->id, $step2->id],
            ParallelStepGroup::SYNC_ALL
        );

        $this->assertInstanceOf(ParallelStepGroup::class, $group);
        $this->assertEquals('Finance & IT Review', $group->name);
        $this->assertEquals(ParallelStepGroup::SYNC_ALL, $group->sync_type);
        $this->assertCount(2, $group->steps);

        // Check steps are updated
        $step1->refresh();
        $this->assertEquals('parallel', $step1->execution_type);
        $this->assertEquals($group->id, $step1->parallel_group_id);
    }

    /** @test */
    public function it_detects_fork_points()
    {
        $forkStep = ApprovalStep::create([
            'workflow_id' => $this->workflow->id,
            'name' => 'Fork Step',
            'sequence' => 1,
            'approval_type' => 'serial',
            'execution_type' => 'fork',
        ]);

        $this->assertTrue($this->manager->isForkPoint($forkStep));
    }

    /** @test */
    public function it_detects_join_points()
    {
        $joinStep = ApprovalStep::create([
            'workflow_id' => $this->workflow->id,
            'name' => 'Join Step',
            'sequence' => 5,
            'approval_type' => 'serial',
            'execution_type' => 'join',
        ]);

        $this->assertTrue($this->manager->isJoinPoint($joinStep));
    }

    /** @test */
    public function it_forks_workflow_into_parallel_paths()
    {
        $forkStep = ApprovalStep::create([
            'workflow_id' => $this->workflow->id,
            'name' => 'Fork Step',
            'sequence' => 1,
            'approval_type' => 'serial',
        ]);

        $step1 = ApprovalStep::create([
            'workflow_id' => $this->workflow->id,
            'name' => 'Parallel Step 1',
            'sequence' => 2,
            'approval_type' => 'serial',
        ]);

        $step2 = ApprovalStep::create([
            'workflow_id' => $this->workflow->id,
            'name' => 'Parallel Step 2',
            'sequence' => 3,
            'approval_type' => 'serial',
        ]);

        $group = $this->manager->createParallelGroup(
            $this->workflow->id,
            'Parallel Group',
            [$step1->id, $step2->id],
            ParallelStepGroup::SYNC_ALL,
            $forkStep->id
        );

        $request = ApprovalRequest::create([
            'workflow_id' => $this->workflow->id,
            'requestable_type' => 'App\\Models\\Test',
            'requestable_id' => 1,
            'requested_by_user_id' => $this->user->id,
            'status' => 'submitted',
            'current_step_id' => $forkStep->id,
        ]);

        $activatedSteps = $this->manager->forkWorkflow($request, $forkStep);

        $this->assertCount(2, $activatedSteps);
        
        // Check execution state was created
        $this->assertDatabaseHas('parallel_execution_states', [
            'approval_request_id' => $request->id,
            'parallel_group_id' => $group->id,
            'status' => 'in_progress',
        ]);

        // Check active parallel steps were created
        $this->assertDatabaseHas('active_parallel_steps', [
            'approval_request_id' => $request->id,
            'step_id' => $step1->id,
        ]);
    }

    /** @test */
    public function it_completes_parallel_step_and_checks_sync_condition()
    {
        $step1 = ApprovalStep::create([
            'workflow_id' => $this->workflow->id,
            'name' => 'Step 1',
            'sequence' => 1,
            'approval_type' => 'serial',
        ]);

        $step2 = ApprovalStep::create([
            'workflow_id' => $this->workflow->id,
            'name' => 'Step 2',
            'sequence' => 2,
            'approval_type' => 'serial',
        ]);

        $joinStep = ApprovalStep::create([
            'workflow_id' => $this->workflow->id,
            'name' => 'Join Step',
            'sequence' => 3,
            'approval_type' => 'serial',
        ]);

        $group = $this->manager->createParallelGroup(
            $this->workflow->id,
            'Test Group',
            [$step1->id, $step2->id],
            ParallelStepGroup::SYNC_ALL,
            null,
            $joinStep->id
        );

        // Refresh steps to get updated parallel_group_id
        $step1->refresh();
        $step2->refresh();

        $request = ApprovalRequest::create([
            'workflow_id' => $this->workflow->id,
            'requestable_type' => 'App\\Models\\Test',
            'requestable_id' => 1,
            'requested_by_user_id' => $this->user->id,
            'status' => 'submitted',
        ]);

        // Create execution state and active steps
        $executionState = ParallelExecutionState::create([
            'approval_request_id' => $request->id,
            'parallel_group_id' => $group->id,
            'status' => 'in_progress',
            'completed_steps' => 0,
            'total_steps' => 2,
            'started_at' => now(),
        ]);

        ActiveParallelStep::create([
            'approval_request_id' => $request->id,
            'step_id' => $step1->id,
            'parallel_group_id' => $group->id,
            'status' => 'pending',
        ]);

        ActiveParallelStep::create([
            'approval_request_id' => $request->id,
            'step_id' => $step2->id,
            'parallel_group_id' => $group->id,
            'status' => 'pending',
        ]);

        // Complete first step - should not trigger join yet
        $result = $this->manager->completeParallelStep($request, $step1, 'approved');
        $this->assertNull($result); // Still waiting

        // Complete second step - should trigger join
        $result = $this->manager->completeParallelStep($request, $step2, 'approved');
        $this->assertInstanceOf(ApprovalStep::class, $result);
        $this->assertEquals($joinStep->id, $result->id);
    }

    /** @test */
    public function it_checks_sync_condition_for_any_type()
    {
        $group = ParallelStepGroup::create([
            'workflow_id' => $this->workflow->id,
            'name' => 'Any Group',
            'sync_type' => ParallelStepGroup::SYNC_ANY,
        ]);

        $this->assertTrue($group->isSyncConditionMet(1, 5)); // Any one completed
        $this->assertFalse($group->isSyncConditionMet(0, 5)); // None completed
    }

    /** @test */
    public function it_checks_sync_condition_for_majority_type()
    {
        $group = ParallelStepGroup::create([
            'workflow_id' => $this->workflow->id,
            'name' => 'Majority Group',
            'sync_type' => ParallelStepGroup::SYNC_MAJORITY,
        ]);

        $this->assertTrue($group->isSyncConditionMet(3, 5)); // 3 out of 5
        $this->assertFalse($group->isSyncConditionMet(2, 5)); // 2 out of 5 (not majority)
    }

    /** @test */
    public function it_validates_parallel_group_configuration()
    {
        $validConfig = [
            'name' => 'Test Group',
            'sync_type' => ParallelStepGroup::SYNC_ALL,
        ];

        $errors = $this->manager->validateParallelGroup($validConfig);
        $this->assertEmpty($errors);

        $invalidConfig = [
            'name' => '',
            'sync_type' => 'invalid',
        ];

        $errors = $this->manager->validateParallelGroup($invalidConfig);
        $this->assertNotEmpty($errors);
    }

    /** @test */
    public function it_gets_parallel_execution_status()
    {
        $group = ParallelStepGroup::create([
            'workflow_id' => $this->workflow->id,
            'name' => 'Test Group',
            'sync_type' => ParallelStepGroup::SYNC_ALL,
        ]);

        $request = ApprovalRequest::create([
            'workflow_id' => $this->workflow->id,
            'requestable_type' => 'App\\Models\\Test',
            'requestable_id' => 1,
            'requested_by_user_id' => $this->user->id,
            'status' => 'submitted',
        ]);

        ParallelExecutionState::create([
            'approval_request_id' => $request->id,
            'parallel_group_id' => $group->id,
            'status' => 'in_progress',
            'completed_steps' => 2,
            'total_steps' => 5,
            'started_at' => now(),
        ]);

        $status = $this->manager->getExecutionStatus($request);

        $this->assertCount(1, $status);
        $this->assertEquals(2, $status[0]['completed_steps']);
        $this->assertEquals(5, $status[0]['total_steps']);
        $this->assertEquals(40.0, $status[0]['completion_percentage']);
    }
}
