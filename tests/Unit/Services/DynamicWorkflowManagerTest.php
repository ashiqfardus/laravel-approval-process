<?php

namespace AshiqFardus\ApprovalProcess\Tests\Unit\Services;

use AshiqFardus\ApprovalProcess\Tests\TestCase;
use AshiqFardus\ApprovalProcess\Services\DynamicWorkflowManager;
use AshiqFardus\ApprovalProcess\Models\Workflow;
use AshiqFardus\ApprovalProcess\Models\ApprovalStep;
use AshiqFardus\ApprovalProcess\Models\ApprovalRequest;
use AshiqFardus\ApprovalProcess\Models\WorkflowVersion;
use AshiqFardus\ApprovalProcess\Models\DynamicStepModification;
use AshiqFardus\ApprovalProcess\Models\DynamicApproverAssignment;
use AshiqFardus\ApprovalProcess\Models\WorkflowModificationRule;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DynamicWorkflowManagerTest extends TestCase
{
    use RefreshDatabase;

    protected DynamicWorkflowManager $manager;
    protected Workflow $workflow;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->manager = new DynamicWorkflowManager();
        $this->user = $this->createUser();
        
        $this->workflow = Workflow::create([
            'name' => 'Dynamic Workflow',
            'model_type' => 'App\\Models\\Test',
            'is_active' => true,
        ]);

        // Create a modification rule to allow changes
        WorkflowModificationRule::create([
            'workflow_id' => $this->workflow->id,
            'rule_type' => WorkflowModificationRule::RULE_ALLOW_STEP_ADDITION,
            'is_active' => true,
        ]);

        WorkflowModificationRule::create([
            'workflow_id' => $this->workflow->id,
            'rule_type' => WorkflowModificationRule::RULE_ALLOW_STEP_REMOVAL,
            'is_active' => true,
        ]);

        WorkflowModificationRule::create([
            'workflow_id' => $this->workflow->id,
            'rule_type' => WorkflowModificationRule::RULE_ALLOW_SKIP_STEP,
            'is_active' => true,
        ]);

        WorkflowModificationRule::create([
            'workflow_id' => $this->workflow->id,
            'rule_type' => WorkflowModificationRule::RULE_ALLOW_APPROVER_CHANGE,
            'is_active' => true,
        ]);
    }

    /** @test */
    public function it_adds_a_step_to_active_request()
    {
        $step1 = ApprovalStep::create([
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
            'status' => 'submitted',
            'current_step_id' => $step1->id,
        ]);

        $newStep = $this->manager->addStepToRequest(
            $request,
            [
                'name' => 'Dynamic Step',
                'sequence' => 2,
                'approval_type' => 'serial',
            ],
            $this->user->id,
            'Additional review required'
        );

        $this->assertInstanceOf(ApprovalStep::class, $newStep);
        $this->assertEquals('Dynamic Step', $newStep->name);

        // Check modification was recorded
        $this->assertDatabaseHas('dynamic_step_modifications', [
            'approval_request_id' => $request->id,
            'step_id' => $newStep->id,
            'modification_type' => 'added',
        ]);

        // Check version was created
        $this->assertDatabaseHas('workflow_versions', [
            'workflow_id' => $this->workflow->id,
            'change_type' => 'step_added',
        ]);
    }

    /** @test */
    public function it_removes_a_step_from_active_request()
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

        $request = ApprovalRequest::create([
            'workflow_id' => $this->workflow->id,
            'requestable_type' => 'App\\Models\\Test',
            'requestable_id' => 1,
            'requested_by_user_id' => $this->user->id,
            'status' => 'submitted',
            'current_step_id' => $step1->id,
        ]);

        $result = $this->manager->removeStepFromRequest(
            $request,
            $step2->id,
            $this->user->id,
            'Step no longer needed'
        );

        $this->assertTrue($result);

        // Check step was marked inactive
        $step2->refresh();
        $this->assertFalse($step2->is_active);

        // Check modification was recorded
        $this->assertDatabaseHas('dynamic_step_modifications', [
            'approval_request_id' => $request->id,
            'step_id' => $step2->id,
            'modification_type' => 'removed',
        ]);
    }

    /** @test */
    public function it_prevents_removing_current_step()
    {
        $step1 = ApprovalStep::create([
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
            'status' => 'submitted',
            'current_step_id' => $step1->id,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cannot remove the current step');

        $this->manager->removeStepFromRequest(
            $request,
            $step1->id,
            $this->user->id
        );
    }

    /** @test */
    public function it_skips_a_step()
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

        $request = ApprovalRequest::create([
            'workflow_id' => $this->workflow->id,
            'requestable_type' => 'App\\Models\\Test',
            'requestable_id' => 1,
            'requested_by_user_id' => $this->user->id,
            'status' => 'submitted',
            'current_step_id' => $step1->id,
        ]);

        $result = $this->manager->skipStep(
            $request,
            $step1->id,
            $this->user->id,
            'Skipping for urgent processing'
        );

        $this->assertTrue($result);

        // Check request moved to next step
        $request->refresh();
        $this->assertEquals($step2->id, $request->current_step_id);

        // Check modification was recorded
        $this->assertDatabaseHas('dynamic_step_modifications', [
            'approval_request_id' => $request->id,
            'step_id' => $step1->id,
            'modification_type' => 'skipped',
        ]);
    }

    /** @test */
    public function it_assigns_dynamic_approver()
    {
        $step1 = ApprovalStep::create([
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
            'status' => 'submitted',
            'current_step_id' => $step1->id,
        ]);

        $newApprover = $this->createUser();

        $assignment = $this->manager->assignDynamicApprover(
            $request,
            $step1->id,
            $newApprover->id,
            $this->user->id,
            DynamicApproverAssignment::TYPE_ADDITION,
            null,
            'Additional approver needed'
        );

        $this->assertInstanceOf(DynamicApproverAssignment::class, $assignment);
        $this->assertEquals($newApprover->id, $assignment->new_approver_id);

        $this->assertDatabaseHas('dynamic_approver_assignments', [
            'approval_request_id' => $request->id,
            'step_id' => $step1->id,
            'new_approver_id' => $newApprover->id,
        ]);
    }

    /** @test */
    public function it_creates_workflow_version_snapshots()
    {
        $step1 = ApprovalStep::create([
            'workflow_id' => $this->workflow->id,
            'name' => 'Step 1',
            'sequence' => 1,
            'approval_type' => 'serial',
        ]);

        $version = WorkflowVersion::createSnapshot(
            $this->workflow,
            WorkflowVersion::CHANGE_TYPE_STEP_ADDED,
            $this->user->id,
            'Initial version'
        );

        $this->assertInstanceOf(WorkflowVersion::class, $version);
        $this->assertEquals(1, $version->version_number);
        $this->assertTrue($version->is_active);

        // Create another version
        $version2 = WorkflowVersion::createSnapshot(
            $this->workflow,
            WorkflowVersion::CHANGE_TYPE_STEP_MODIFIED,
            $this->user->id,
            'Modified step'
        );

        $this->assertEquals(2, $version2->version_number);
        
        // Previous version should be inactive
        $version->refresh();
        $this->assertFalse($version->is_active);
    }

    /** @test */
    public function it_gets_request_modifications()
    {
        $step1 = ApprovalStep::create([
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
            'status' => 'submitted',
            'current_step_id' => $step1->id,
        ]);

        DynamicStepModification::create([
            'approval_request_id' => $request->id,
            'step_id' => $step1->id,
            'modification_type' => 'skipped',
            'reason' => 'Test skip',
            'modified_by_user_id' => $this->user->id,
            'is_applied' => true,
        ]);

        $modifications = $this->manager->getRequestModifications($request);

        $this->assertArrayHasKey('step_modifications', $modifications);
        $this->assertArrayHasKey('approver_assignments', $modifications);
        $this->assertCount(1, $modifications['step_modifications']);
    }

    /** @test */
    public function it_gets_version_history()
    {
        WorkflowVersion::create([
            'workflow_id' => $this->workflow->id,
            'version_number' => 1,
            'workflow_snapshot' => [],
            'steps_snapshot' => [],
            'change_type' => 'created',
            'changed_by_user_id' => $this->user->id,
            'is_active' => false,
        ]);

        WorkflowVersion::create([
            'workflow_id' => $this->workflow->id,
            'version_number' => 2,
            'workflow_snapshot' => [],
            'steps_snapshot' => [],
            'change_type' => 'step_added',
            'changed_by_user_id' => $this->user->id,
            'is_active' => true,
        ]);

        $history = $this->manager->getVersionHistory($this->workflow->id);

        $this->assertCount(2, $history);
        $this->assertEquals(2, $history[0]['version_number']); // Latest first
    }

    /** @test */
    public function it_checks_if_workflow_allows_modifications()
    {
        $this->assertTrue($this->manager->allowsModifications($this->workflow->id));

        // Create workflow without rules
        $workflow2 = Workflow::create([
            'name' => 'Locked Workflow',
            'model_type' => 'App\\Models\\Test2',
            'is_active' => true,
        ]);

        $this->assertFalse($this->manager->allowsModifications($workflow2->id));
    }

    /** @test */
    public function it_validates_modification_rules()
    {
        $validRule = [
            'rule_type' => WorkflowModificationRule::RULE_ALLOW_STEP_ADDITION,
            'requires_approval' => false,
        ];

        $errors = $this->manager->validateModificationRule($validRule);
        $this->assertEmpty($errors);

        $invalidRule = [
            'rule_type' => '',
            'requires_approval' => true,
            'approval_required_from_user_id' => null,
        ];

        $errors = $this->manager->validateModificationRule($invalidRule);
        $this->assertNotEmpty($errors);
    }

    /** @test */
    public function it_gets_modification_statistics()
    {
        $step1 = ApprovalStep::create([
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
            'status' => 'submitted',
            'current_step_id' => $step1->id,
        ]);

        DynamicStepModification::create([
            'approval_request_id' => $request->id,
            'step_id' => $step1->id,
            'modification_type' => 'added',
            'modified_by_user_id' => $this->user->id,
            'is_applied' => true,
        ]);

        DynamicStepModification::create([
            'approval_request_id' => $request->id,
            'step_id' => $step1->id,
            'modification_type' => 'skipped',
            'modified_by_user_id' => $this->user->id,
            'is_applied' => true,
        ]);

        $stats = $this->manager->getModificationStats($this->workflow->id);

        $this->assertEquals($this->workflow->id, $stats['workflow_id']);
        $this->assertEquals(2, $stats['total_modifications']);
        $this->assertArrayHasKey('modifications_by_type', $stats);
    }

    /** @test */
    public function it_gets_dynamic_approvers_for_step()
    {
        $step1 = ApprovalStep::create([
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
            'status' => 'submitted',
            'current_step_id' => $step1->id,
        ]);

        $newApprover = $this->createUser();

        DynamicApproverAssignment::create([
            'approval_request_id' => $request->id,
            'step_id' => $step1->id,
            'new_approver_id' => $newApprover->id,
            'assignment_type' => 'addition',
            'assigned_by_user_id' => $this->user->id,
            'is_active' => true,
        ]);

        $approvers = $this->manager->getDynamicApprovers($request, $step1->id);

        $this->assertCount(1, $approvers);
    }
}
