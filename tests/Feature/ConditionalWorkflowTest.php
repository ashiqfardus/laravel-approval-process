<?php

namespace AshiqFardus\ApprovalProcess\Tests\Feature;

use AshiqFardus\ApprovalProcess\Tests\TestCase;
use AshiqFardus\ApprovalProcess\Models\Workflow;
use AshiqFardus\ApprovalProcess\Models\ApprovalStep;
use AshiqFardus\ApprovalProcess\Models\WorkflowCondition;
use AshiqFardus\ApprovalProcess\Models\WorkflowConditionGroup;
use AshiqFardus\ApprovalProcess\Models\ApprovalRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ConditionalWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $workflow;
    protected $step1;
    protected $step2;
    protected $step3;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->withoutMiddleware();
        
        $this->user = $this->createUser();
        
        $this->workflow = Workflow::create([
            'name' => 'Conditional Workflow',
            'model_type' => 'App\\Models\\PurchaseOrder',
            'is_active' => true,
        ]);
        
        $this->step1 = ApprovalStep::create([
            'workflow_id' => $this->workflow->id,
            'name' => 'Manager Approval',
            'sequence' => 1,
            'approval_type' => 'serial',
        ]);
        
        $this->step2 = ApprovalStep::create([
            'workflow_id' => $this->workflow->id,
            'name' => 'Director Approval',
            'sequence' => 2,
            'approval_type' => 'serial',
        ]);
        
        $this->step3 = ApprovalStep::create([
            'workflow_id' => $this->workflow->id,
            'name' => 'CEO Approval',
            'sequence' => 3,
            'approval_type' => 'serial',
        ]);
    }

    /** @test */
    public function it_can_create_a_workflow_condition()
    {
        $response = $this->postJson("/api/approval-process/workflows/{$this->workflow->id}/conditions", [
            'from_step_id' => $this->step1->id,
            'to_step_id' => $this->step3->id,
            'name' => 'High Value Route',
            'field' => 'amount',
            'operator' => '>',
            'value' => [10000],
            'priority' => 10,
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure(['id', 'workflow_id', 'from_step_id', 'to_step_id', 'field', 'operator', 'value']);
        
        $this->assertDatabaseHas('workflow_conditions', [
            'workflow_id' => $this->workflow->id,
            'from_step_id' => $this->step1->id,
            'to_step_id' => $this->step3->id,
            'field' => 'amount',
            'operator' => '>',
        ]);
    }

    /** @test */
    public function it_can_list_workflow_conditions()
    {
        WorkflowCondition::create([
            'workflow_id' => $this->workflow->id,
            'from_step_id' => $this->step1->id,
            'to_step_id' => $this->step2->id,
            'field' => 'amount',
            'operator' => '>',
            'value' => [5000],
        ]);

        WorkflowCondition::create([
            'workflow_id' => $this->workflow->id,
            'from_step_id' => $this->step1->id,
            'to_step_id' => $this->step3->id,
            'field' => 'amount',
            'operator' => '>',
            'value' => [10000],
        ]);

        $response = $this->getJson("/api/approval-process/workflows/{$this->workflow->id}/conditions");

        $response->assertStatus(200);
        $response->assertJsonCount(2);
    }

    /** @test */
    public function it_can_update_a_workflow_condition()
    {
        $condition = WorkflowCondition::create([
            'workflow_id' => $this->workflow->id,
            'from_step_id' => $this->step1->id,
            'to_step_id' => $this->step2->id,
            'field' => 'amount',
            'operator' => '>',
            'value' => [5000],
        ]);

        $response = $this->putJson("/api/approval-process/workflows/{$this->workflow->id}/conditions/{$condition->id}", [
            'from_step_id' => $this->step1->id,
            'to_step_id' => $this->step3->id,
            'field' => 'amount',
            'operator' => '>=',
            'value' => [10000],
            'priority' => 20,
        ]);

        $response->assertStatus(200);
        
        $condition->refresh();
        $this->assertEquals('>=', $condition->operator);
        $this->assertEquals([10000], $condition->value);
        $this->assertEquals(20, $condition->priority);
    }

    /** @test */
    public function it_can_delete_a_workflow_condition()
    {
        $condition = WorkflowCondition::create([
            'workflow_id' => $this->workflow->id,
            'from_step_id' => $this->step1->id,
            'to_step_id' => $this->step2->id,
            'field' => 'amount',
            'operator' => '>',
            'value' => [5000],
        ]);

        $response = $this->deleteJson("/api/approval-process/workflows/{$this->workflow->id}/conditions/{$condition->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('workflow_conditions', ['id' => $condition->id]);
    }

    /** @test */
    public function it_can_test_a_condition_with_sample_data()
    {
        $condition = WorkflowCondition::create([
            'workflow_id' => $this->workflow->id,
            'from_step_id' => $this->step1->id,
            'to_step_id' => $this->step3->id,
            'field' => 'amount',
            'operator' => '>',
            'value' => [10000],
        ]);

        $response = $this->postJson("/api/approval-process/workflows/{$this->workflow->id}/conditions/{$condition->id}/test", [
            'data' => ['amount' => 15000],
        ]);

        $response->assertStatus(200);
        $response->assertJson(['result' => true, 'message' => 'Condition passed']);

        $response = $this->postJson("/api/approval-process/workflows/{$this->workflow->id}/conditions/{$condition->id}/test", [
            'data' => ['amount' => 5000],
        ]);

        $response->assertStatus(200);
        $response->assertJson(['result' => false, 'message' => 'Condition failed']);
    }

    /** @test */
    public function it_can_get_possible_next_steps()
    {
        WorkflowCondition::create([
            'workflow_id' => $this->workflow->id,
            'from_step_id' => $this->step1->id,
            'to_step_id' => $this->step2->id,
            'field' => 'amount',
            'operator' => '<=',
            'value' => [10000],
            'priority' => 5,
        ]);

        WorkflowCondition::create([
            'workflow_id' => $this->workflow->id,
            'from_step_id' => $this->step1->id,
            'to_step_id' => $this->step3->id,
            'field' => 'amount',
            'operator' => '>',
            'value' => [10000],
            'priority' => 10,
        ]);

        $response = $this->getJson("/api/approval-process/workflows/{$this->workflow->id}/steps/{$this->step1->id}/possible-next");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'workflow_id',
            'current_step_id',
            'possible_next_steps' => [
                '*' => ['step_id', 'step_name', 'condition'],
            ],
        ]);
        $response->assertJsonCount(2, 'possible_next_steps');
    }

    /** @test */
    public function it_can_get_supported_operators()
    {
        $response = $this->getJson("/api/approval-process/workflows/conditions/operators");

        $response->assertStatus(200);
        $response->assertJsonStructure(['operators', 'descriptions']);
    }

    /** @test */
    public function it_validates_condition_data()
    {
        $response = $this->postJson("/api/approval-process/workflows/{$this->workflow->id}/conditions", [
            'from_step_id' => $this->step1->id,
            'to_step_id' => $this->step2->id,
            'field' => '', // Invalid: empty field
            'operator' => 'invalid_operator', // Invalid operator
            'value' => null,
        ]);

        $response->assertStatus(422);
        $response->assertJsonStructure(['errors']);
    }

    // Condition Groups

    /** @test */
    public function it_can_create_a_condition_group()
    {
        $condition1 = WorkflowCondition::create([
            'workflow_id' => $this->workflow->id,
            'from_step_id' => $this->step1->id,
            'to_step_id' => $this->step2->id,
            'field' => 'amount',
            'operator' => '>',
            'value' => [5000],
        ]);

        $condition2 = WorkflowCondition::create([
            'workflow_id' => $this->workflow->id,
            'from_step_id' => $this->step1->id,
            'to_step_id' => $this->step2->id,
            'field' => 'department',
            'operator' => '=',
            'value' => ['IT'],
        ]);

        $response = $this->postJson("/api/approval-process/workflows/{$this->workflow->id}/condition-groups", [
            'name' => 'High Value IT',
            'logic_operator' => 'and',
            'priority' => 10,
            'condition_ids' => [$condition1->id, $condition2->id],
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure(['id', 'workflow_id', 'name', 'logic_operator', 'conditions']);
        
        $this->assertDatabaseHas('workflow_condition_groups', [
            'workflow_id' => $this->workflow->id,
            'name' => 'High Value IT',
            'logic_operator' => 'and',
        ]);
    }

    /** @test */
    public function it_can_test_a_condition_group()
    {
        $group = WorkflowConditionGroup::create([
            'workflow_id' => $this->workflow->id,
            'name' => 'High Value IT',
            'logic_operator' => 'and',
        ]);

        $condition1 = WorkflowCondition::create([
            'workflow_id' => $this->workflow->id,
            'from_step_id' => $this->step1->id,
            'to_step_id' => $this->step2->id,
            'field' => 'amount',
            'operator' => '>',
            'value' => [5000],
        ]);

        $condition2 = WorkflowCondition::create([
            'workflow_id' => $this->workflow->id,
            'from_step_id' => $this->step1->id,
            'to_step_id' => $this->step2->id,
            'field' => 'department',
            'operator' => '=',
            'value' => ['IT'],
        ]);

        $group->conditions()->attach($condition1->id, ['sequence' => 1]);
        $group->conditions()->attach($condition2->id, ['sequence' => 2]);

        $response = $this->postJson("/api/approval-process/workflows/{$this->workflow->id}/condition-groups/{$group->id}/test", [
            'data' => ['amount' => 6000, 'department' => 'IT'],
        ]);

        $response->assertStatus(200);
        $response->assertJson(['result' => true, 'message' => 'Group conditions passed']);
    }
}
