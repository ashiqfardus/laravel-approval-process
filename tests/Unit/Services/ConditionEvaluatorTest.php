<?php

namespace AshiqFardus\ApprovalProcess\Tests\Unit\Services;

use AshiqFardus\ApprovalProcess\Tests\TestCase;
use AshiqFardus\ApprovalProcess\Services\ConditionEvaluator;
use AshiqFardus\ApprovalProcess\Models\WorkflowCondition;
use AshiqFardus\ApprovalProcess\Models\WorkflowConditionGroup;
use AshiqFardus\ApprovalProcess\Models\Workflow;
use AshiqFardus\ApprovalProcess\Models\ApprovalStep;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ConditionEvaluatorTest extends TestCase
{
    use RefreshDatabase;

    protected ConditionEvaluator $evaluator;
    protected Workflow $workflow;
    protected ApprovalStep $step1;
    protected ApprovalStep $step2;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->evaluator = new ConditionEvaluator();
        
        $this->workflow = Workflow::create([
            'name' => 'Test Workflow',
            'model_type' => 'App\\Models\\Test',
            'is_active' => true,
        ]);
        
        $this->step1 = ApprovalStep::create([
            'workflow_id' => $this->workflow->id,
            'name' => 'Step 1',
            'sequence' => 1,
            'approval_type' => 'serial',
        ]);
        
        $this->step2 = ApprovalStep::create([
            'workflow_id' => $this->workflow->id,
            'name' => 'Step 2',
            'sequence' => 2,
            'approval_type' => 'serial',
        ]);
    }

    /** @test */
    public function it_evaluates_equals_operator()
    {
        $condition = WorkflowCondition::create([
            'workflow_id' => $this->workflow->id,
            'from_step_id' => $this->step1->id,
            'to_step_id' => $this->step2->id,
            'field' => 'status',
            'operator' => '=',
            'value' => ['approved'],
        ]);

        $this->assertTrue($this->evaluator->evaluateCondition($condition, ['status' => 'approved']));
        $this->assertFalse($this->evaluator->evaluateCondition($condition, ['status' => 'rejected']));
    }

    /** @test */
    public function it_evaluates_greater_than_operator()
    {
        $condition = WorkflowCondition::create([
            'workflow_id' => $this->workflow->id,
            'from_step_id' => $this->step1->id,
            'to_step_id' => $this->step2->id,
            'field' => 'amount',
            'operator' => '>',
            'value' => [1000],
        ]);

        $this->assertTrue($this->evaluator->evaluateCondition($condition, ['amount' => 1500]));
        $this->assertFalse($this->evaluator->evaluateCondition($condition, ['amount' => 500]));
        $this->assertFalse($this->evaluator->evaluateCondition($condition, ['amount' => 1000]));
    }

    /** @test */
    public function it_evaluates_less_than_operator()
    {
        $condition = WorkflowCondition::create([
            'workflow_id' => $this->workflow->id,
            'from_step_id' => $this->step1->id,
            'to_step_id' => $this->step2->id,
            'field' => 'amount',
            'operator' => '<',
            'value' => [1000],
        ]);

        $this->assertTrue($this->evaluator->evaluateCondition($condition, ['amount' => 500]));
        $this->assertFalse($this->evaluator->evaluateCondition($condition, ['amount' => 1500]));
    }

    /** @test */
    public function it_evaluates_in_operator()
    {
        $condition = WorkflowCondition::create([
            'workflow_id' => $this->workflow->id,
            'from_step_id' => $this->step1->id,
            'to_step_id' => $this->step2->id,
            'field' => 'department',
            'operator' => 'in',
            'value' => ['IT', 'HR', 'Finance'],
        ]);

        $this->assertTrue($this->evaluator->evaluateCondition($condition, ['department' => 'IT']));
        $this->assertTrue($this->evaluator->evaluateCondition($condition, ['department' => 'HR']));
        $this->assertFalse($this->evaluator->evaluateCondition($condition, ['department' => 'Marketing']));
    }

    /** @test */
    public function it_evaluates_between_operator()
    {
        $condition = WorkflowCondition::create([
            'workflow_id' => $this->workflow->id,
            'from_step_id' => $this->step1->id,
            'to_step_id' => $this->step2->id,
            'field' => 'amount',
            'operator' => 'between',
            'value' => [1000, 5000],
        ]);

        $this->assertTrue($this->evaluator->evaluateCondition($condition, ['amount' => 3000]));
        $this->assertTrue($this->evaluator->evaluateCondition($condition, ['amount' => 1000]));
        $this->assertTrue($this->evaluator->evaluateCondition($condition, ['amount' => 5000]));
        $this->assertFalse($this->evaluator->evaluateCondition($condition, ['amount' => 500]));
        $this->assertFalse($this->evaluator->evaluateCondition($condition, ['amount' => 6000]));
    }

    /** @test */
    public function it_evaluates_contains_operator()
    {
        $condition = WorkflowCondition::create([
            'workflow_id' => $this->workflow->id,
            'from_step_id' => $this->step1->id,
            'to_step_id' => $this->step2->id,
            'field' => 'description',
            'operator' => 'contains',
            'value' => ['urgent'],
        ]);

        $this->assertTrue($this->evaluator->evaluateCondition($condition, ['description' => 'This is urgent']));
        $this->assertFalse($this->evaluator->evaluateCondition($condition, ['description' => 'Normal request']));
    }

    /** @test */
    public function it_evaluates_starts_with_operator()
    {
        $condition = WorkflowCondition::create([
            'workflow_id' => $this->workflow->id,
            'from_step_id' => $this->step1->id,
            'to_step_id' => $this->step2->id,
            'field' => 'code',
            'operator' => 'starts_with',
            'value' => ['PO-'],
        ]);

        $this->assertTrue($this->evaluator->evaluateCondition($condition, ['code' => 'PO-12345']));
        $this->assertFalse($this->evaluator->evaluateCondition($condition, ['code' => 'INV-12345']));
    }

    /** @test */
    public function it_evaluates_is_null_operator()
    {
        $condition = WorkflowCondition::create([
            'workflow_id' => $this->workflow->id,
            'from_step_id' => $this->step1->id,
            'to_step_id' => $this->step2->id,
            'field' => 'approved_by',
            'operator' => 'is_null',
            'value' => [],
        ]);

        $this->assertTrue($this->evaluator->evaluateCondition($condition, ['approved_by' => null]));
        $this->assertFalse($this->evaluator->evaluateCondition($condition, ['approved_by' => 123]));
    }

    /** @test */
    public function it_evaluates_multiple_conditions_with_and_logic()
    {
        $condition1 = WorkflowCondition::create([
            'workflow_id' => $this->workflow->id,
            'from_step_id' => $this->step1->id,
            'to_step_id' => $this->step2->id,
            'field' => 'amount',
            'operator' => '>',
            'value' => [1000],
        ]);

        $condition2 = WorkflowCondition::create([
            'workflow_id' => $this->workflow->id,
            'from_step_id' => $this->step1->id,
            'to_step_id' => $this->step2->id,
            'field' => 'department',
            'operator' => '=',
            'value' => ['IT'],
        ]);

        $data = ['amount' => 1500, 'department' => 'IT'];
        $this->assertTrue($this->evaluator->evaluateConditions([$condition1, $condition2], $data, 'and'));

        $data = ['amount' => 1500, 'department' => 'HR'];
        $this->assertFalse($this->evaluator->evaluateConditions([$condition1, $condition2], $data, 'and'));
    }

    /** @test */
    public function it_evaluates_multiple_conditions_with_or_logic()
    {
        $condition1 = WorkflowCondition::create([
            'workflow_id' => $this->workflow->id,
            'from_step_id' => $this->step1->id,
            'to_step_id' => $this->step2->id,
            'field' => 'amount',
            'operator' => '>',
            'value' => [1000],
        ]);

        $condition2 = WorkflowCondition::create([
            'workflow_id' => $this->workflow->id,
            'from_step_id' => $this->step1->id,
            'to_step_id' => $this->step2->id,
            'field' => 'department',
            'operator' => '=',
            'value' => ['IT'],
        ]);

        $data = ['amount' => 500, 'department' => 'IT'];
        $this->assertTrue($this->evaluator->evaluateConditions([$condition1, $condition2], $data, 'or'));

        $data = ['amount' => 500, 'department' => 'HR'];
        $this->assertFalse($this->evaluator->evaluateConditions([$condition1, $condition2], $data, 'or'));
    }

    /** @test */
    public function it_finds_next_step_based_on_conditions()
    {
        $step3 = ApprovalStep::create([
            'workflow_id' => $this->workflow->id,
            'name' => 'Step 3',
            'sequence' => 3,
            'approval_type' => 'serial',
        ]);

        WorkflowCondition::create([
            'workflow_id' => $this->workflow->id,
            'from_step_id' => $this->step1->id,
            'to_step_id' => $step3->id,
            'field' => 'amount',
            'operator' => '>',
            'value' => [5000],
            'priority' => 10,
        ]);

        WorkflowCondition::create([
            'workflow_id' => $this->workflow->id,
            'from_step_id' => $this->step1->id,
            'to_step_id' => $this->step2->id,
            'field' => 'amount',
            'operator' => '<=',
            'value' => [5000],
            'priority' => 5,
        ]);

        // High amount should route to step 3
        $nextStepId = $this->evaluator->findNextStep($this->workflow->id, $this->step1->id, ['amount' => 6000]);
        $this->assertEquals($step3->id, $nextStepId);

        // Low amount should route to step 2
        $nextStepId = $this->evaluator->findNextStep($this->workflow->id, $this->step1->id, ['amount' => 3000]);
        $this->assertEquals($this->step2->id, $nextStepId);
    }

    /** @test */
    public function it_validates_condition_configuration()
    {
        $validCondition = [
            'field' => 'amount',
            'operator' => '>',
            'value' => [1000],
        ];
        
        $errors = $this->evaluator->validateCondition($validCondition);
        $this->assertEmpty($errors);

        $invalidCondition = [
            'field' => '',
            'operator' => 'invalid',
            'value' => null,
        ];
        
        $errors = $this->evaluator->validateCondition($invalidCondition);
        $this->assertNotEmpty($errors);
    }

    /** @test */
    public function it_evaluates_condition_groups()
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
            'value' => [1000],
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

        $data = ['amount' => 1500, 'department' => 'IT'];
        $this->assertTrue($this->evaluator->evaluateGroup($group, $data));

        $data = ['amount' => 1500, 'department' => 'HR'];
        $this->assertFalse($this->evaluator->evaluateGroup($group, $data));
    }
}
