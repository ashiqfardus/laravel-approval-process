<?php

namespace AshiqFardus\ApprovalProcess\Tests\Unit\Services;

use AshiqFardus\ApprovalProcess\Tests\TestCase;
use AshiqFardus\ApprovalProcess\Services\ChangeTrackingService;
use AshiqFardus\ApprovalProcess\Models\Workflow;
use AshiqFardus\ApprovalProcess\Models\ApprovalStep;
use AshiqFardus\ApprovalProcess\Models\ApprovalRequest;
use AshiqFardus\ApprovalProcess\Models\ApprovalChangeLog;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ChangeTrackingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ChangeTrackingService $service;
    protected $user;
    protected $workflow;
    protected $request;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = new ChangeTrackingService();
        $this->user = $this->createUser();
        
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
        
        $this->request = ApprovalRequest::create([
            'workflow_id' => $this->workflow->id,
            'requestable_type' => 'stdClass',
            'requestable_id' => 1,
            'requested_by_user_id' => $this->user->id,
            'status' => ApprovalRequest::STATUS_DRAFT,
            'data_snapshot' => ['id' => 1, 'name' => 'Original', 'amount' => 100],
        ]);
    }

    /** @test */
    public function it_tracks_changes_between_old_and_new_data()
    {
        $oldData = ['name' => 'Old Name', 'amount' => 100];
        $newData = ['name' => 'New Name', 'amount' => 200];
        
        $changes = $this->service->trackChanges(
            $this->request,
            $oldData,
            $newData,
            $this->user->id
        );
        
        $this->assertCount(2, $changes);
        $this->assertDatabaseHas('approval_change_logs', [
            'approval_request_id' => $this->request->id,
            'field_name' => 'name',
            'old_value' => 'Old Name',
            'new_value' => 'New Name',
        ]);
        $this->assertDatabaseHas('approval_change_logs', [
            'approval_request_id' => $this->request->id,
            'field_name' => 'amount',
            'old_value' => '100',
            'new_value' => '200',
        ]);
    }

    /** @test */
    public function it_ignores_unchanged_fields()
    {
        $oldData = ['name' => 'Same Name', 'amount' => 100];
        $newData = ['name' => 'Same Name', 'amount' => 100];
        
        $changes = $this->service->trackChanges(
            $this->request,
            $oldData,
            $newData,
            $this->user->id
        );
        
        $this->assertCount(0, $changes);
    }

    /** @test */
    public function it_ignores_specified_fields()
    {
        $oldData = ['id' => 1, 'name' => 'Test', 'created_at' => '2024-01-01'];
        $newData = ['id' => 2, 'name' => 'Updated', 'created_at' => '2024-01-02'];
        
        $changes = $this->service->trackChanges(
            $this->request,
            $oldData,
            $newData,
            $this->user->id,
            ['ignored_fields' => ['id', 'created_at', 'updated_at']]
        );
        
        $this->assertCount(1, $changes);
        $this->assertEquals('name', $changes->first()->field_name);
    }

    /** @test */
    public function it_only_tracks_specified_fields()
    {
        $oldData = ['name' => 'Old', 'amount' => 100, 'status' => 'draft'];
        $newData = ['name' => 'New', 'amount' => 200, 'status' => 'active'];
        
        $changes = $this->service->trackChanges(
            $this->request,
            $oldData,
            $newData,
            $this->user->id,
            ['only_fields' => ['name', 'amount']]
        );
        
        $this->assertCount(2, $changes);
        $this->assertFalse($changes->contains('field_name', 'status'));
    }

    /** @test */
    public function it_tracks_array_values()
    {
        $oldData = ['tags' => ['tag1', 'tag2']];
        $newData = ['tags' => ['tag1', 'tag3']];
        
        $changes = $this->service->trackChanges(
            $this->request,
            $oldData,
            $newData,
            $this->user->id
        );
        
        $this->assertCount(1, $changes);
        $change = $changes->first();
        $this->assertEquals('tags', $change->field_name);
        $this->assertIsArray($change->old_value);
        $this->assertIsArray($change->new_value);
    }

    /** @test */
    public function it_tracks_null_values()
    {
        $oldData = ['name' => 'Test', 'description' => 'Has description'];
        $newData = ['name' => 'Test', 'description' => null];
        
        $changes = $this->service->trackChanges(
            $this->request,
            $oldData,
            $newData,
            $this->user->id
        );
        
        $this->assertCount(1, $changes);
        $change = $changes->first();
        $this->assertEquals('description', $change->field_name);
        $this->assertEquals('Has description', $change->old_value);
        $this->assertNull($change->new_value);
    }

    /** @test */
    public function it_tracks_new_fields()
    {
        $oldData = ['name' => 'Test'];
        $newData = ['name' => 'Test', 'amount' => 100];
        
        $changes = $this->service->trackChanges(
            $this->request,
            $oldData,
            $newData,
            $this->user->id
        );
        
        $this->assertCount(1, $changes);
        $this->assertEquals('amount', $changes->first()->field_name);
        $this->assertNull($changes->first()->old_value);
        $this->assertEquals('100', $changes->first()->new_value);
    }

    /** @test */
    public function it_tracks_removed_fields()
    {
        $oldData = ['name' => 'Test', 'amount' => 100];
        $newData = ['name' => 'Test'];
        
        $changes = $this->service->trackChanges(
            $this->request,
            $oldData,
            $newData,
            $this->user->id
        );
        
        $this->assertCount(1, $changes);
        $this->assertEquals('amount', $changes->first()->field_name);
        $this->assertEquals('100', $changes->first()->old_value);
        $this->assertNull($changes->first()->new_value);
    }

    /** @test */
    public function it_tracks_model_changes()
    {
        $oldModel = new \stdClass();
        $oldModel->id = 1;
        $oldModel->name = 'Old Name';
        $oldModel->amount = 100;
        
        $newModel = new \stdClass();
        $newModel->id = 1;
        $newModel->name = 'New Name';
        $newModel->amount = 200;
        
        // Convert to arrays for comparison
        $oldAttributes = ['id' => 1, 'name' => 'Old Name', 'amount' => 100];
        $newAttributes = ['id' => 1, 'name' => 'New Name', 'amount' => 200];
        
        $changes = $this->service->trackChanges(
            $this->request,
            $oldAttributes,
            $newAttributes,
            $this->user->id
        );
        
        $this->assertCount(2, $changes);
    }

    /** @test */
    public function it_tracks_snapshot_changes()
    {
        // Set initial snapshot
        $this->request->update(['data_snapshot' => ['id' => 1, 'name' => 'Original']]);
        
        $newSnapshot = ['id' => 1, 'name' => 'Updated'];
        
        $changes = $this->service->trackSnapshotChanges(
            $this->request,
            $newSnapshot,
            $this->user->id,
            ['ignored_fields' => ['id']] // Ignore id field
        );
        
        $this->assertCount(1, $changes);
        $this->assertEquals('name', $changes->first()->field_name);
    }

    /** @test */
    public function it_gets_change_summary()
    {
        // Create some changes
        ApprovalChangeLog::create([
            'approval_request_id' => $this->request->id,
            'user_id' => $this->user->id,
            'field_name' => 'name',
            'old_value' => 'Old',
            'new_value' => 'New',
        ]);
        
        ApprovalChangeLog::create([
            'approval_request_id' => $this->request->id,
            'user_id' => $this->user->id,
            'field_name' => 'amount',
            'old_value' => '100',
            'new_value' => '200',
        ]);
        
        $summary = $this->service->getChangeSummary($this->request);
        
        $this->assertEquals(2, $summary['total_changes']);
        $this->assertContains('name', $summary['changed_fields']);
        $this->assertContains('amount', $summary['changed_fields']);
        $this->assertArrayHasKey('changes_by_field', $summary);
        $this->assertArrayHasKey('latest_change_at', $summary);
    }

    /** @test */
    public function it_gets_changes_for_specific_fields()
    {
        ApprovalChangeLog::create([
            'approval_request_id' => $this->request->id,
            'user_id' => $this->user->id,
            'field_name' => 'name',
            'old_value' => 'Old',
            'new_value' => 'New',
        ]);
        
        ApprovalChangeLog::create([
            'approval_request_id' => $this->request->id,
            'user_id' => $this->user->id,
            'field_name' => 'amount',
            'old_value' => '100',
            'new_value' => '200',
        ]);
        
        $changes = $this->service->getChangesForFields($this->request, ['name']);
        
        $this->assertCount(1, $changes);
        $this->assertEquals('name', $changes->first()->field_name);
    }
}
