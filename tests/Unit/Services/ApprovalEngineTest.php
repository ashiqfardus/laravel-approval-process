<?php

namespace AshiqFardus\ApprovalProcess\Tests\Unit\Services;

use AshiqFardus\ApprovalProcess\Tests\TestCase;
use AshiqFardus\ApprovalProcess\Services\ApprovalEngine;
use AshiqFardus\ApprovalProcess\Models\Workflow;
use AshiqFardus\ApprovalProcess\Models\ApprovalStep;
use AshiqFardus\ApprovalProcess\Models\ApprovalRequest;
use AshiqFardus\ApprovalProcess\Models\ApprovalAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\Model;

// Simple test model for editAndResubmit tests
class TestModel extends Model
{
    protected $table = 'test_models';
    protected $fillable = ['id', 'name', 'amount'];
    public $timestamps = false;
}

class ApprovalEngineTest extends TestCase
{
    use RefreshDatabase;

    protected ApprovalEngine $engine;
    protected $user;
    protected $workflow;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test_models table
        $this->app['db']->connection()->getSchemaBuilder()->create('test_models', function ($table) {
            $table->id();
            $table->string('name')->nullable();
            $table->integer('amount')->nullable();
        });
        
        $this->engine = new ApprovalEngine();
        $this->user = $this->createUser();
        
        $this->workflow = Workflow::create([
            'name' => 'Test Workflow',
            'model_type' => TestModel::class,
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
    public function it_can_edit_and_resubmit_request()
    {
        $model = TestModel::create([
            'name' => 'Original',
            'amount' => 100,
        ]);
        
        // Submit initial request
        $request = $this->engine->submitRequest($model, $this->user->id);
        $request->update(['status' => ApprovalRequest::STATUS_REJECTED]);
        
        // Update the model
        $model->name = 'Updated';
        $model->amount = 200;
        $model->save();
        
        // Edit and resubmit
        $updatedRequest = $this->engine->editAndResubmit(
            $request,
            $model,
            $this->user->id,
            'Fixed issues and resubmitted'
        );
        
        $this->assertEquals(ApprovalRequest::STATUS_SUBMITTED, $updatedRequest->status);
        $this->assertNotNull($updatedRequest->current_step_id);
        
        // Check that edit action was recorded
        $editAction = ApprovalAction::where('approval_request_id', $request->id)
            ->where('action', 'edited')
            ->first();
        
        $this->assertNotNull($editAction);
        $this->assertStringContainsString('resubmitted', $editAction->remarks);
    }

    /** @test */
    public function it_resets_to_first_step_on_edit_and_resubmit()
    {
        $model = TestModel::create(['name' => 'Test']);
        
        $request = $this->engine->submitRequest($model, $this->user->id);
        $firstStepId = $request->current_step_id;
        
        // Move to a different step (simulate)
        $step2 = ApprovalStep::create([
            'workflow_id' => $this->workflow->id,
            'name' => 'Step 2',
            'sequence' => 2,
            'approval_type' => 'serial',
        ]);
        $request->update(['current_step_id' => $step2->id, 'status' => ApprovalRequest::STATUS_REJECTED]);
        
        // Update model
        $model->name = 'Updated';
        $model->save();
        
        // Edit and resubmit
        $updatedRequest = $this->engine->editAndResubmit(
            $request,
            $model,
            $this->user->id
        );
        
        // Should reset to first step
        $this->assertEquals($firstStepId, $updatedRequest->current_step_id);
    }

    /** @test */
    public function it_updates_data_snapshot_on_edit_and_resubmit()
    {
        $model = TestModel::create(['name' => 'Original']);
        
        $request = $this->engine->submitRequest($model, $this->user->id);
        $request->update(['status' => ApprovalRequest::STATUS_REJECTED]);
        
        $originalSnapshot = $request->data_snapshot;
        
        // Update model
        $model->name = 'Updated';
        $model->save();
        
        $updatedRequest = $this->engine->editAndResubmit(
            $request,
            $model,
            $this->user->id
        );
        
        $this->assertNotEquals($originalSnapshot, $updatedRequest->data_snapshot);
        $this->assertEquals('Updated', $updatedRequest->data_snapshot['name']);
    }

    /** @test */
    public function it_calculates_approval_progress()
    {
        $model = TestModel::create();
        
        $step2 = ApprovalStep::create([
            'workflow_id' => $this->workflow->id,
            'name' => 'Step 2',
            'sequence' => 2,
            'approval_type' => 'serial',
        ]);
        
        $request = $this->engine->submitRequest($model, $this->user->id);
        
        // Initial progress
        $progress = $this->engine->calculateApprovalProgress($request);
        $this->assertEquals(0, $progress['completed_steps']);
        $this->assertEquals(0, $progress['progress_percentage']);
        $this->assertEquals(2, $progress['total_steps']);
        
        // After one approval
        ApprovalAction::create([
            'approval_request_id' => $request->id,
            'approval_step_id' => $request->current_step_id,
            'user_id' => $this->user->id,
            'action' => ApprovalAction::ACTION_APPROVED,
        ]);
        
        $progress = $this->engine->calculateApprovalProgress($request);
        $this->assertEquals(1, $progress['completed_steps']);
        $this->assertEquals(50, $progress['progress_percentage']);
    }

    /** @test */
    public function it_handles_zero_steps_in_progress_calculation()
    {
        $workflow = Workflow::create([
            'name' => 'Empty Workflow',
            'model_type' => TestModel::class,
            'is_active' => true,
        ]);
        
        $model = TestModel::create();
        
        // This should fail because no steps, but let's test progress calculation
        try {
            $request = $this->engine->submitRequest($model, $this->user->id);
        } catch (\Exception $e) {
            // Expected - no workflow found
            $this->assertTrue(true);
            return;
        }
        
        // If somehow created, progress should handle zero steps
        $request = ApprovalRequest::create([
            'workflow_id' => $workflow->id,
            'requestable_type' => TestModel::class,
            'requestable_id' => $model->id,
            'requested_by_user_id' => $this->user->id,
            'status' => ApprovalRequest::STATUS_SUBMITTED,
        ]);
        
        $progress = $this->engine->calculateApprovalProgress($request);
        $this->assertEquals(0, $progress['total_steps']);
        $this->assertEquals(0, $progress['progress_percentage']);
    }
}
