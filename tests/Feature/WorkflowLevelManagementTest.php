<?php

namespace AshiqFardus\ApprovalProcess\Tests\Feature;

use AshiqFardus\ApprovalProcess\Tests\TestCase;
use AshiqFardus\ApprovalProcess\Models\Workflow;
use AshiqFardus\ApprovalProcess\Models\ApprovalStep;
use AshiqFardus\ApprovalProcess\Models\ApprovalRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

class WorkflowLevelManagementTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $workflow;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = $this->createUser();
        
        // Disable auth middleware for this test class
        $this->withoutMiddleware();
        
        $this->workflow = Workflow::create([
            'name' => 'Test Workflow',
            'model_type' => 'stdClass',
            'is_active' => true,
        ]);
        
        ApprovalStep::create([
            'workflow_id' => $this->workflow->id,
            'name' => 'Step 1',
            'sequence' => 1,
            'approval_type' => 'serial',
        ]);
    }

    /** @test */
    public function it_can_get_all_steps_for_workflow()
    {
        ApprovalStep::create([
            'workflow_id' => $this->workflow->id,
            'name' => 'Step 2',
            'sequence' => 2,
            'approval_type' => 'serial',
        ]);
        
        $response = $this
            ->getJson("/api/approval-process/workflows/{$this->workflow->id}/steps");
        
        $response->assertStatus(200);
        $response->assertJsonCount(2);
        $response->assertJsonStructure([
            '*' => ['id', 'name', 'sequence', 'approval_type']
        ]);
    }

    /** @test */
    public function it_can_add_step_to_workflow()
    {
        $response = $this
            ->postJson("/api/approval-process/workflows/{$this->workflow->id}/steps", [
                'name' => 'New Step',
                'description' => 'New step description',
                'sequence' => 2,
                'approval_type' => 'parallel',
                'level_alias' => 'Reviewed By',
                'sla_hours' => 48,
            ]);
        
        $response->assertStatus(201);
        $response->assertJsonStructure(['id', 'name', 'sequence']);
        
        $this->assertDatabaseHas('approval_steps', [
            'workflow_id' => $this->workflow->id,
            'name' => 'New Step',
            'sequence' => 2,
        ]);
    }

    /** @test */
    public function it_auto_increments_sequence_when_not_provided()
    {
        $response = $this
            ->postJson("/api/approval-process/workflows/{$this->workflow->id}/steps", [
                'name' => 'Auto Sequence Step',
                'approval_type' => 'serial',
            ]);
        
        $response->assertStatus(201);
        $this->assertEquals(2, $response->json('sequence'));
    }

    /** @test */
    public function it_shifts_existing_steps_when_adding_at_existing_sequence()
    {
        ApprovalStep::create([
            'workflow_id' => $this->workflow->id,
            'name' => 'Step 2',
            'sequence' => 2,
            'approval_type' => 'serial',
        ]);
        
        $response = $this
            ->postJson("/api/approval-process/workflows/{$this->workflow->id}/steps", [
                'name' => 'Inserted Step',
                'sequence' => 2,
                'approval_type' => 'serial',
            ]);
        
        $response->assertStatus(201);
        
        // Original step 2 should now be step 3
        $this->assertDatabaseHas('approval_steps', [
            'name' => 'Step 2',
            'sequence' => 3,
        ]);
        
        // New step should be at sequence 2
        $this->assertDatabaseHas('approval_steps', [
            'name' => 'Inserted Step',
            'sequence' => 2,
        ]);
    }

    /** @test */
    public function it_can_update_step_details()
    {
        $step = ApprovalStep::create([
            'workflow_id' => $this->workflow->id,
            'name' => 'Original Name',
            'sequence' => 2,
            'approval_type' => 'serial',
            'sla_hours' => 24,
        ]);
        
        $response = $this
            ->putJson("/api/approval-process/workflows/{$this->workflow->id}/steps/{$step->id}", [
                'name' => 'Updated Name',
                'sla_hours' => 48,
                'level_alias' => 'Checked By',
            ]);
        
        $response->assertStatus(200);
        $response->assertJson(['name' => 'Updated Name']);
        
        $step->refresh();
        $this->assertEquals('Updated Name', $step->name);
        $this->assertEquals(48, $step->sla_hours);
        $this->assertEquals('Checked By', $step->level_alias);
    }

    /** @test */
    public function it_can_reorder_step_sequence()
    {
        $step1 = ApprovalStep::where('workflow_id', $this->workflow->id)
            ->where('sequence', 1)
            ->first();
        
        $step2 = ApprovalStep::create([
            'workflow_id' => $this->workflow->id,
            'name' => 'Step 2',
            'sequence' => 2,
            'approval_type' => 'serial',
        ]);
        
        $step3 = ApprovalStep::create([
            'workflow_id' => $this->workflow->id,
            'name' => 'Step 3',
            'sequence' => 3,
            'approval_type' => 'serial',
        ]);
        
        $response = $this
            ->putJson("/api/approval-process/workflows/{$this->workflow->id}/steps/{$step1->id}", [
                'sequence' => 3,
            ]);
        
        $response->assertStatus(200);
        
        $step1->refresh();
        $step2->refresh();
        $step3->refresh();
        
        $this->assertEquals(3, $step1->sequence);
        $this->assertEquals(1, $step2->sequence); // Shifted up
        $this->assertEquals(2, $step3->sequence); // Shifted up
    }

    /** @test */
    public function it_can_bulk_reorder_steps()
    {
        $step1 = ApprovalStep::where('workflow_id', $this->workflow->id)
            ->where('sequence', 1)
            ->first();
        
        $step2 = ApprovalStep::create([
            'workflow_id' => $this->workflow->id,
            'name' => 'Step 2',
            'sequence' => 2,
            'approval_type' => 'serial',
        ]);
        
        $step3 = ApprovalStep::create([
            'workflow_id' => $this->workflow->id,
            'name' => 'Step 3',
            'sequence' => 3,
            'approval_type' => 'serial',
        ]);
        
        // Reorder: step3, step1, step2
        $response = $this
            ->postJson("/api/approval-process/workflows/{$this->workflow->id}/steps/reorder", [
                'step_ids' => [$step3->id, $step1->id, $step2->id],
            ]);
        
        $response->assertStatus(200);
        
        $step1->refresh();
        $step2->refresh();
        $step3->refresh();
        
        $this->assertEquals(2, $step1->sequence);
        $this->assertEquals(3, $step2->sequence);
        $this->assertEquals(1, $step3->sequence);
    }

    /** @test */
    public function it_can_remove_step_from_workflow()
    {
        $step = ApprovalStep::create([
            'workflow_id' => $this->workflow->id,
            'name' => 'Step to Remove',
            'sequence' => 2,
            'approval_type' => 'serial',
        ]);
        
        $response = $this
            ->deleteJson("/api/approval-process/workflows/{$this->workflow->id}/steps/{$step->id}");
        
        $response->assertStatus(204);
        
        $this->assertDatabaseMissing('approval_steps', [
            'id' => $step->id,
        ]);
    }

    /** @test */
    public function it_reorders_remaining_steps_after_removal()
    {
        $step1 = ApprovalStep::where('workflow_id', $this->workflow->id)
            ->where('sequence', 1)
            ->first();
        
        $step2 = ApprovalStep::create([
            'workflow_id' => $this->workflow->id,
            'name' => 'Step 2',
            'sequence' => 2,
            'approval_type' => 'serial',
        ]);
        
        $step3 = ApprovalStep::create([
            'workflow_id' => $this->workflow->id,
            'name' => 'Step 3',
            'sequence' => 3,
            'approval_type' => 'serial',
        ]);
        
        // Remove step2
        $this->actingAs($this->user, 'api')
            ->deleteJson("/api/approval-process/workflows/{$this->workflow->id}/steps/{$step2->id}");
        
        $step3->refresh();
        $this->assertEquals(2, $step3->sequence); // Should shift from 3 to 2
    }

    /** @test */
    public function it_rejects_removal_of_step_with_pending_approvals()
    {
        $step = ApprovalStep::create([
            'workflow_id' => $this->workflow->id,
            'name' => 'Step with Pending',
            'sequence' => 2,
            'approval_type' => 'serial',
        ]);
        
        ApprovalRequest::create([
            'workflow_id' => $this->workflow->id,
            'requestable_type' => 'stdClass',
            'requestable_id' => 1,
            'requested_by_user_id' => $this->user->id,
            'status' => ApprovalRequest::STATUS_PENDING,
            'current_step_id' => $step->id,
        ]);
        
        $response = $this
            ->deleteJson("/api/approval-process/workflows/{$this->workflow->id}/steps/{$step->id}");
        
        $response->assertStatus(422);
        $response->assertJson(['message' => 'Cannot remove step with pending approval requests. Disable it instead.']);
    }

    /** @test */
    public function it_rejects_update_of_step_from_different_workflow()
    {
        $otherWorkflow = Workflow::create([
            'name' => 'Other Workflow',
            'model_type' => 'stdClass',
            'is_active' => true,
        ]);
        
        $otherStep = ApprovalStep::create([
            'workflow_id' => $otherWorkflow->id,
            'name' => 'Other Step',
            'sequence' => 1,
            'approval_type' => 'serial',
        ]);
        
        $response = $this
            ->putJson("/api/approval-process/workflows/{$this->workflow->id}/steps/{$otherStep->id}", [
                'name' => 'Updated',
            ]);
        
        $response->assertStatus(422);
        $response->assertJson(['message' => 'Step does not belong to this workflow']);
    }

    /** @test */
    public function it_validates_step_ids_in_bulk_reorder()
    {
        $otherWorkflow = Workflow::create([
            'name' => 'Other Workflow',
            'model_type' => 'stdClass',
            'is_active' => true,
        ]);
        
        $otherStep = ApprovalStep::create([
            'workflow_id' => $otherWorkflow->id,
            'name' => 'Other Step',
            'sequence' => 1,
            'approval_type' => 'serial',
        ]);
        
        $response = $this
            ->postJson("/api/approval-process/workflows/{$this->workflow->id}/steps/reorder", [
                'step_ids' => [$otherStep->id],
            ]);
        
        $response->assertStatus(422);
        $response->assertJson(['message' => 'Some steps do not belong to this workflow']);
    }
}
