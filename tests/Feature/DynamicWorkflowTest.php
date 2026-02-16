<?php

namespace AshiqFardus\ApprovalProcess\Tests\Feature;

use AshiqFardus\ApprovalProcess\Tests\TestCase;
use AshiqFardus\ApprovalProcess\Models\Workflow;
use AshiqFardus\ApprovalProcess\Models\ApprovalStep;
use AshiqFardus\ApprovalProcess\Models\ApprovalRequest;
use AshiqFardus\ApprovalProcess\Models\WorkflowModificationRule;
use AshiqFardus\ApprovalProcess\Models\WorkflowVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DynamicWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $workflow;
    protected $step1;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->withoutMiddleware();
        
        $this->user = $this->createUser();
        
        $this->workflow = Workflow::create([
            'name' => 'Dynamic Workflow',
            'model_type' => 'App\\Models\\Document',
            'is_active' => true,
        ]);

        $this->step1 = ApprovalStep::create([
            'workflow_id' => $this->workflow->id,
            'name' => 'Manager Approval',
            'sequence' => 1,
            'approval_type' => 'serial',
        ]);

        // Create modification rules
        WorkflowModificationRule::create([
            'workflow_id' => $this->workflow->id,
            'rule_type' => 'allow_step_addition',
            'is_active' => true,
        ]);

        WorkflowModificationRule::create([
            'workflow_id' => $this->workflow->id,
            'rule_type' => 'allow_step_removal',
            'is_active' => true,
        ]);

        WorkflowModificationRule::create([
            'workflow_id' => $this->workflow->id,
            'rule_type' => 'allow_skip_step',
            'is_active' => true,
        ]);

        WorkflowModificationRule::create([
            'workflow_id' => $this->workflow->id,
            'rule_type' => 'allow_approver_change',
            'is_active' => true,
        ]);
    }

    /** @test */
    public function it_can_add_step_to_active_request()
    {
        $request = ApprovalRequest::create([
            'workflow_id' => $this->workflow->id,
            'requestable_type' => 'App\\Models\\Document',
            'requestable_id' => 1,
            'requested_by_user_id' => $this->user->id,
            'status' => 'submitted',
            'current_step_id' => $this->step1->id,
        ]);

        $response = $this->postJson("/api/approval-process/requests/{$request->id}/add-step", [
            'step_data' => [
                'name' => 'Additional Review',
                'sequence' => 2,
                'approval_type' => 'serial',
            ],
            'reason' => 'Extra review needed',
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure(['message', 'step']);
        
        $this->assertDatabaseHas('approval_steps', [
            'workflow_id' => $this->workflow->id,
            'name' => 'Additional Review',
        ]);
    }

    /** @test */
    public function it_can_skip_a_step()
    {
        $step2 = ApprovalStep::create([
            'workflow_id' => $this->workflow->id,
            'name' => 'Director Approval',
            'sequence' => 2,
            'approval_type' => 'serial',
        ]);

        $request = ApprovalRequest::create([
            'workflow_id' => $this->workflow->id,
            'requestable_type' => 'App\\Models\\Document',
            'requestable_id' => 1,
            'requested_by_user_id' => $this->user->id,
            'status' => 'submitted',
            'current_step_id' => $this->step1->id,
        ]);

        $response = $this->postJson("/api/approval-process/requests/{$request->id}/steps/{$this->step1->id}/skip", [
            'reason' => 'Urgent processing',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Step skipped successfully']);
        
        // Check request moved to next step
        $request->refresh();
        $this->assertEquals($step2->id, $request->current_step_id);
    }

    /** @test */
    public function it_can_assign_dynamic_approver()
    {
        $request = ApprovalRequest::create([
            'workflow_id' => $this->workflow->id,
            'requestable_type' => 'App\\Models\\Document',
            'requestable_id' => 1,
            'requested_by_user_id' => $this->user->id,
            'status' => 'submitted',
            'current_step_id' => $this->step1->id,
        ]);

        $newApprover = $this->createUser();

        $response = $this->postJson("/api/approval-process/requests/{$request->id}/steps/{$this->step1->id}/assign-approver", [
            'new_approver_id' => $newApprover->id,
            'assignment_type' => 'addition',
            'reason' => 'Additional review required',
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure(['message', 'assignment']);
    }

    /** @test */
    public function it_can_get_request_modifications()
    {
        $request = ApprovalRequest::create([
            'workflow_id' => $this->workflow->id,
            'requestable_type' => 'App\\Models\\Document',
            'requestable_id' => 1,
            'requested_by_user_id' => $this->user->id,
            'status' => 'submitted',
            'current_step_id' => $this->step1->id,
        ]);

        $response = $this->getJson("/api/approval-process/requests/{$request->id}/modifications");

        $response->assertStatus(200);
        $response->assertJsonStructure(['step_modifications', 'approver_assignments']);
    }

    /** @test */
    public function it_can_get_version_history()
    {
        WorkflowVersion::create([
            'workflow_id' => $this->workflow->id,
            'version_number' => 1,
            'workflow_snapshot' => [],
            'steps_snapshot' => [],
            'change_type' => 'created',
            'changed_by_user_id' => $this->user->id,
        ]);

        $response = $this->getJson("/api/approval-process/workflows/{$this->workflow->id}/versions");

        $response->assertStatus(200);
        $response->assertJsonStructure(['workflow_id', 'versions']);
    }

    /** @test */
    public function it_can_create_modification_rule()
    {
        $workflow2 = Workflow::create([
            'name' => 'Test Workflow 2',
            'model_type' => 'App\\Models\\Test',
            'is_active' => true,
        ]);

        $response = $this->postJson("/api/approval-process/workflows/{$workflow2->id}/modification-rules", [
            'rule_type' => 'allow_step_addition',
            'requires_approval' => false,
            'is_active' => true,
        ]);

        $response->assertStatus(201);
        
        $this->assertDatabaseHas('workflow_modification_rules', [
            'workflow_id' => $workflow2->id,
            'rule_type' => 'allow_step_addition',
        ]);
    }

    /** @test */
    public function it_can_get_modification_stats()
    {
        $response = $this->getJson("/api/approval-process/workflows/{$this->workflow->id}/modification-stats");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'workflow_id',
            'workflow_name',
            'total_modifications',
            'modifications_by_type',
            'total_versions',
            'current_version',
            'allows_modifications',
        ]);
    }

    /** @test */
    public function it_validates_modification_rule_data()
    {
        $workflow2 = Workflow::create([
            'name' => 'Test Workflow 2',
            'model_type' => 'App\\Models\\Test',
            'is_active' => true,
        ]);

        $response = $this->postJson("/api/approval-process/workflows/{$workflow2->id}/modification-rules", [
            'rule_type' => '', // Invalid
            'requires_approval' => true,
            'approval_required_from_user_id' => null, // Missing when required
        ]);

        $response->assertStatus(422);
        $response->assertJsonStructure(['errors']);
    }
}
