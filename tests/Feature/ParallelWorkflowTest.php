<?php

namespace AshiqFardus\ApprovalProcess\Tests\Feature;

use AshiqFardus\ApprovalProcess\Tests\TestCase;
use AshiqFardus\ApprovalProcess\Models\Workflow;
use AshiqFardus\ApprovalProcess\Models\ApprovalStep;
use AshiqFardus\ApprovalProcess\Models\ParallelStepGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ParallelWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $workflow;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->withoutMiddleware();
        
        $this->user = $this->createUser();
        
        $this->workflow = Workflow::create([
            'name' => 'Parallel Workflow',
            'model_type' => 'App\\Models\\PurchaseOrder',
            'is_active' => true,
        ]);
    }

    /** @test */
    public function it_can_create_a_parallel_group()
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

        $response = $this->postJson("/api/approval-process/workflows/{$this->workflow->id}/parallel-groups", [
            'name' => 'Finance & IT Review',
            'sync_type' => 'all',
            'step_ids' => [$step1->id, $step2->id],
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure(['id', 'workflow_id', 'name', 'sync_type', 'steps']);
        
        $this->assertDatabaseHas('parallel_step_groups', [
            'workflow_id' => $this->workflow->id,
            'name' => 'Finance & IT Review',
            'sync_type' => 'all',
        ]);
    }

    /** @test */
    public function it_can_list_parallel_groups()
    {
        $step1 = ApprovalStep::create([
            'workflow_id' => $this->workflow->id,
            'name' => 'Step 1',
            'sequence' => 1,
            'approval_type' => 'serial',
        ]);

        ParallelStepGroup::create([
            'workflow_id' => $this->workflow->id,
            'name' => 'Group 1',
            'sync_type' => 'all',
        ]);

        ParallelStepGroup::create([
            'workflow_id' => $this->workflow->id,
            'name' => 'Group 2',
            'sync_type' => 'any',
        ]);

        $response = $this->getJson("/api/approval-process/workflows/{$this->workflow->id}/parallel-groups");

        $response->assertStatus(200);
        $response->assertJsonCount(2);
    }

    /** @test */
    public function it_can_update_a_parallel_group()
    {
        $group = ParallelStepGroup::create([
            'workflow_id' => $this->workflow->id,
            'name' => 'Original Name',
            'sync_type' => 'all',
        ]);

        $response = $this->putJson("/api/approval-process/workflows/{$this->workflow->id}/parallel-groups/{$group->id}", [
            'name' => 'Updated Name',
            'sync_type' => 'majority',
        ]);

        $response->assertStatus(200);
        
        $group->refresh();
        $this->assertEquals('Updated Name', $group->name);
        $this->assertEquals('majority', $group->sync_type);
    }

    /** @test */
    public function it_can_delete_a_parallel_group()
    {
        $group = ParallelStepGroup::create([
            'workflow_id' => $this->workflow->id,
            'name' => 'Test Group',
            'sync_type' => 'all',
        ]);

        $response = $this->deleteJson("/api/approval-process/workflows/{$this->workflow->id}/parallel-groups/{$group->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('parallel_step_groups', ['id' => $group->id]);
    }

    /** @test */
    public function it_can_get_supported_sync_types()
    {
        $response = $this->getJson("/api/approval-process/workflows/parallel/sync-types");

        $response->assertStatus(200);
        $response->assertJsonStructure(['sync_types', 'descriptions']);
    }

    /** @test */
    public function it_can_simulate_parallel_execution()
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

        $step3 = ApprovalStep::create([
            'workflow_id' => $this->workflow->id,
            'name' => 'Step 3',
            'sequence' => 3,
            'approval_type' => 'serial',
        ]);

        $group = ParallelStepGroup::create([
            'workflow_id' => $this->workflow->id,
            'name' => 'Test Group',
            'sync_type' => 'majority',
        ]);

        // Manually update steps to belong to group
        $step1->update(['parallel_group_id' => $group->id, 'execution_type' => 'parallel']);
        $step2->update(['parallel_group_id' => $group->id, 'execution_type' => 'parallel']);
        $step3->update(['parallel_group_id' => $group->id, 'execution_type' => 'parallel']);

        $response = $this->postJson("/api/approval-process/workflows/{$this->workflow->id}/parallel-groups/{$group->id}/simulate", [
            'completed_steps' => 2,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'total_steps' => 3,
            'completed_steps' => 2,
            'sync_type' => 'majority',
            'sync_condition_met' => true,
        ]);
    }

    /** @test */
    public function it_validates_parallel_group_data()
    {
        $response = $this->postJson("/api/approval-process/workflows/{$this->workflow->id}/parallel-groups", [
            'name' => '',
            'sync_type' => 'invalid_type',
        ]);

        $response->assertStatus(422);
        $response->assertJsonStructure(['errors']);
    }
}
