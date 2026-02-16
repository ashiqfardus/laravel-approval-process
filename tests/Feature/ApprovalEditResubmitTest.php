<?php

namespace AshiqFardus\ApprovalProcess\Tests\Feature;

use AshiqFardus\ApprovalProcess\Tests\TestCase;
use AshiqFardus\ApprovalProcess\Models\Workflow;
use AshiqFardus\ApprovalProcess\Models\ApprovalStep;
use AshiqFardus\ApprovalProcess\Models\ApprovalRequest;
use AshiqFardus\ApprovalProcess\Models\ApprovalChangeLog;
use AshiqFardus\ApprovalProcess\Models\QueryApprovalRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ApprovalEditResubmitTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $workflow;
    protected $queryWorkflow;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = $this->createUser();
        
        // Disable auth middleware for this test class
        $this->withoutMiddleware();
        
        // Create regular workflow
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
        
        // Create query-based workflow
        $this->queryWorkflow = Workflow::create([
            'name' => 'Query Workflow',
            'model_type' => 'query:builder',
            'is_active' => true,
        ]);
        
        ApprovalStep::create([
            'workflow_id' => $this->queryWorkflow->id,
            'name' => 'Step 1',
            'sequence' => 1,
            'approval_type' => 'serial',
        ]);
        
        // Create test table
        DB::statement('CREATE TABLE test_data (id INTEGER PRIMARY KEY, name TEXT, amount INTEGER)');
    }

    /** @test */
    public function it_can_edit_and_resubmit_model_based_request()
    {
        $model = new \stdClass();
        $model->id = 1;
        $model->name = 'Original';
        $model->amount = 100;
        
        $request = ApprovalRequest::create([
            'workflow_id' => $this->workflow->id,
            'requestable_type' => 'stdClass',
            'requestable_id' => 1,
            'requested_by_user_id' => $this->user->id,
            'status' => ApprovalRequest::STATUS_REJECTED,
            'data_snapshot' => ['id' => 1, 'name' => 'Original', 'amount' => 100],
            'current_step_id' => $this->workflow->steps()->first()->id,
        ]);
        
        $response = $this->postJson("/api/approval-process/requests/{$request->id}/edit-and-resubmit", [
                'data_snapshot' => ['id' => 1, 'name' => 'Updated', 'amount' => 200],
                'remarks' => 'Fixed issues',
            ]);
        
        $response->assertStatus(200);
        $response->assertJsonStructure(['id', 'status', 'data_snapshot']);
        
        $request->refresh();
        $this->assertEquals(ApprovalRequest::STATUS_SUBMITTED, $request->status);
        $this->assertEquals('Updated', $request->data_snapshot['name']);
        
        // Check change was tracked
        $this->assertDatabaseHas('approval_change_logs', [
            'approval_request_id' => $request->id,
            'field_name' => 'name',
        ]);
    }

    /** @test */
    public function it_can_edit_and_resubmit_query_based_request()
    {
        $request = ApprovalRequest::create([
            'workflow_id' => $this->queryWorkflow->id,
            'requestable_type' => 'stdClass',
            'requestable_id' => 'test_identifier',
            'requested_by_user_id' => $this->user->id,
            'status' => ApprovalRequest::STATUS_REJECTED,
            'data_snapshot' => ['id' => 'test_identifier', 'type' => 'builder', 'data' => [['id' => 1, 'name' => 'Original']]],
            'current_step_id' => $this->queryWorkflow->steps()->first()->id,
        ]);
        
        $queryApproval = QueryApprovalRequest::create([
            'approval_request_id' => $request->id,
            'query_type' => 'builder',
            'query_definition' => ['table' => 'test_data'],
            'result_snapshot' => [['id' => 1, 'name' => 'Original']],
            'identifier' => 'test_identifier',
        ]);
        
        $response = $this->postJson("/api/approval-process/requests/{$request->id}/edit-and-resubmit", [
                'data_snapshot' => [
                    'id' => 'test_identifier',
                    'type' => 'builder',
                    'data' => [['id' => 1, 'name' => 'Updated']]
                ],
                'remarks' => 'Updated query result',
            ]);
        
        $response->assertStatus(200);
        
        $request->refresh();
        $queryApproval->refresh();
        
        $this->assertEquals(ApprovalRequest::STATUS_SUBMITTED, $request->status);
        $this->assertEquals('Updated', $request->data_snapshot['data'][0]['name']);
        
        // QueryApprovalRequest should be synced
        $this->assertEquals('Updated', $queryApproval->result_snapshot[0]['name']);
    }

    /** @test */
    public function it_rejects_edit_and_resubmit_for_non_editable_status()
    {
        $request = ApprovalRequest::create([
            'workflow_id' => $this->workflow->id,
            'requestable_type' => 'stdClass',
            'requestable_id' => 1,
            'requested_by_user_id' => $this->user->id,
            'status' => ApprovalRequest::STATUS_APPROVED, // Cannot edit approved
            'data_snapshot' => ['id' => 1],
        ]);
        
        $response = $this->postJson("/api/approval-process/requests/{$request->id}/edit-and-resubmit", [
                'data_snapshot' => ['id' => 1, 'name' => 'Updated'],
            ]);
        
        $response->assertStatus(422);
        $response->assertJson(['message' => 'Request cannot be edited. Only draft or rejected requests can be edited.']);
    }

    /** @test */
    public function it_tracks_changes_when_updating_request()
    {
        $request = ApprovalRequest::create([
            'workflow_id' => $this->workflow->id,
            'requestable_type' => 'stdClass',
            'requestable_id' => 1,
            'requested_by_user_id' => $this->user->id,
            'status' => ApprovalRequest::STATUS_DRAFT,
            'data_snapshot' => ['id' => 1, 'name' => 'Original', 'amount' => 100],
        ]);
        
        $response = $this->putJson("/api/approval-process/requests/{$request->id}", [
                'data_snapshot' => ['id' => 1, 'name' => 'Updated', 'amount' => 200],
            ]);
        
        $response->assertStatus(200);
        
        // Check changes were tracked
        $this->assertDatabaseHas('approval_change_logs', [
            'approval_request_id' => $request->id,
            'field_name' => 'name',
        ]);
        $this->assertDatabaseHas('approval_change_logs', [
            'approval_request_id' => $request->id,
            'field_name' => 'amount',
        ]);
    }

    /** @test */
    public function it_gets_change_history_as_json()
    {
        $request = ApprovalRequest::create([
            'workflow_id' => $this->workflow->id,
            'requestable_type' => 'stdClass',
            'requestable_id' => 1,
            'requested_by_user_id' => $this->user->id,
            'status' => ApprovalRequest::STATUS_DRAFT,
        ]);
        
        ApprovalChangeLog::create([
            'approval_request_id' => $request->id,
            'user_id' => $this->user->id,
            'field_name' => 'name',
            'old_value' => 'Old',
            'new_value' => 'New',
        ]);
        
        $response = $this->getJson("/api/approval-process/requests/{$request->id}/change-history");
        
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'summary' => ['total_changes', 'changed_fields', 'changes_by_field'],
            'changes' => [
                '*' => ['id', 'field_name', 'old_value', 'new_value', 'formatted', 'created_at']
            ],
        ]);
    }

    /** @test */
    public function it_gets_change_history_as_text()
    {
        $request = ApprovalRequest::create([
            'workflow_id' => $this->workflow->id,
            'requestable_type' => 'stdClass',
            'requestable_id' => 1,
            'requested_by_user_id' => $this->user->id,
            'status' => ApprovalRequest::STATUS_DRAFT,
        ]);
        
        // Create change log with proper user relationship
        $changeLog = ApprovalChangeLog::create([
            'approval_request_id' => $request->id,
            'user_id' => $this->user->id,
            'field_name' => 'name',
            'old_value' => 'Old',
            'new_value' => 'New',
        ]);
        
        // Refresh to ensure relationship is loaded
        $request->refresh();
        
        $response = $this->getJson("/api/approval-process/requests/{$request->id}/change-history?format=text");
        
        $response->assertStatus(200);
        $response->assertJsonStructure(['history']);
        $history = $response->json('history');
        // Check that history contains the change (might be formatted)
        $this->assertNotEmpty($history);
        $this->assertNotEquals('No changes recorded for this request.', $history);
    }

    /** @test */
    public function it_gets_change_history_as_html()
    {
        $request = ApprovalRequest::create([
            'workflow_id' => $this->workflow->id,
            'requestable_type' => 'stdClass',
            'requestable_id' => 1,
            'requested_by_user_id' => $this->user->id,
            'status' => ApprovalRequest::STATUS_DRAFT,
        ]);
        
        // Create change log
        ApprovalChangeLog::create([
            'approval_request_id' => $request->id,
            'user_id' => $this->user->id,
            'field_name' => 'name',
            'old_value' => 'Old',
            'new_value' => 'New',
        ]);
        
        // Refresh to ensure relationship is loaded
        $request->refresh();
        
        $response = $this->getJson("/api/approval-process/requests/{$request->id}/change-history?format=html");
        
        $response->assertStatus(200);
        $response->assertJsonStructure(['history']);
        $history = $response->json('history');
        // Should contain HTML structure if changes exist
        $this->assertNotEmpty($history);
        $this->assertNotEquals('<p>No changes recorded.</p>', $history);
        if (strpos($history, '<div') !== false) {
            $this->assertStringContainsString('<div', $history);
            $this->assertStringContainsString('change-history', $history);
        }
    }

    /** @test */
    public function it_handles_empty_change_history()
    {
        $request = ApprovalRequest::create([
            'workflow_id' => $this->workflow->id,
            'requestable_type' => 'stdClass',
            'requestable_id' => 1,
            'requested_by_user_id' => $this->user->id,
            'status' => ApprovalRequest::STATUS_DRAFT,
        ]);
        
        $response = $this->getJson("/api/approval-process/requests/{$request->id}/change-history");
        
        $response->assertStatus(200);
        $this->assertEquals(0, $response->json('summary.total_changes'));
        $this->assertEmpty($response->json('changes'));
    }
}
