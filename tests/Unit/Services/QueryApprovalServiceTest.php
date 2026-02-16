<?php

namespace AshiqFardus\ApprovalProcess\Tests\Unit\Services;

use AshiqFardus\ApprovalProcess\Tests\TestCase;
use AshiqFardus\ApprovalProcess\Services\QueryApprovalService;
use AshiqFardus\ApprovalProcess\Models\Workflow;
use AshiqFardus\ApprovalProcess\Models\ApprovalStep;
use AshiqFardus\ApprovalProcess\Models\ApprovalRequest;
use AshiqFardus\ApprovalProcess\Models\QueryApprovalRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class QueryApprovalServiceTest extends TestCase
{
    use RefreshDatabase;

    protected QueryApprovalService $service;
    protected $user;
    protected $workflow;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = app(QueryApprovalService::class);
        $this->user = $this->createUser();
        
        // Create a test table for query testing
        DB::statement('CREATE TABLE test_data (id INTEGER PRIMARY KEY, name TEXT, amount INTEGER)');
        DB::table('test_data')->insert([
            ['id' => 1, 'name' => 'Test Item 1', 'amount' => 1000],
            ['id' => 2, 'name' => 'Test Item 2', 'amount' => 2000],
        ]);
        
        // Create workflow for query-based approvals
        $this->workflow = Workflow::create([
            'name' => 'Query Approval Workflow',
            'model_type' => 'query:builder',
            'is_active' => true,
        ]);
        
        ApprovalStep::create([
            'workflow_id' => $this->workflow->id,
            'name' => 'Level 1',
            'sequence' => 1,
            'approval_type' => 'serial',
        ]);
    }

    /** @test */
    public function it_can_submit_builder_approval()
    {
        $query = DB::table('test_data')->where('id', 1);
        
        $request = $this->service->submitBuilderApproval($query, $this->user->id);
        
        $this->assertInstanceOf(ApprovalRequest::class, $request);
        $this->assertDatabaseHas('approval_requests', [
            'id' => $request->id,
            'requested_by_user_id' => $this->user->id,
        ]);
        
        $this->assertDatabaseHas('query_approval_requests', [
            'approval_request_id' => $request->id,
            'query_type' => QueryApprovalRequest::TYPE_BUILDER,
        ]);
    }

    /** @test */
    public function it_can_submit_sql_approval()
    {
        // Create SQL workflow
        Workflow::create([
            'name' => 'SQL Query Approval',
            'model_type' => 'query:sql',
            'is_active' => true,
        ])->steps()->create([
            'name' => 'Level 1',
            'sequence' => 1,
            'approval_type' => 'serial',
        ]);
        
        $sql = 'SELECT * FROM test_data WHERE id = ?';
        $bindings = [1];
        
        $request = $this->service->submitSqlApproval($sql, $bindings, $this->user->id);
        
        $this->assertInstanceOf(ApprovalRequest::class, $request);
        $this->assertDatabaseHas('query_approval_requests', [
            'approval_request_id' => $request->id,
            'query_type' => QueryApprovalRequest::TYPE_SQL,
        ]);
    }

    /** @test */
    public function it_stores_query_result_snapshot()
    {
        $query = DB::table('test_data')->where('id', 1);
        
        $request = $this->service->submitBuilderApproval($query, $this->user->id);
        $queryApproval = $request->queryApproval;
        
        $this->assertNotNull($queryApproval);
        $this->assertNotEmpty($queryApproval->result_snapshot);
        $this->assertIsArray($queryApproval->result_snapshot);
    }

    /** @test */
    public function it_can_get_current_query_result()
    {
        $query = DB::table('test_data')->where('id', 1);
        
        $request = $this->service->submitBuilderApproval($query, $this->user->id);
        $currentResult = $this->service->getCurrentQueryResult($request);
        
        $this->assertIsArray($currentResult);
        $this->assertNotEmpty($currentResult);
    }

    /** @test */
    public function it_can_detect_query_result_changes()
    {
        $query = DB::table('test_data')->where('id', 1);
        
        $request = $this->service->submitBuilderApproval($query, $this->user->id);
        
        // Initially, no change
        $this->assertFalse($this->service->hasQueryResultChanged($request));
        
        // Update the data
        DB::table('test_data')->where('id', 1)->update(['amount' => 5000]);
        
        // Now it should detect change
        $this->assertTrue($this->service->hasQueryResultChanged($request));
    }

    /** @test */
    public function it_can_refresh_query_snapshot()
    {
        $query = DB::table('test_data')->where('id', 1);
        
        $request = $this->service->submitBuilderApproval($query, $this->user->id);
        $originalSnapshot = $request->queryApproval->result_snapshot;
        
        // Update the data
        DB::table('test_data')->where('id', 1)->update(['amount' => 5000]);
        
        // Refresh snapshot
        $this->service->refreshQuerySnapshot($request);
        
        $request->refresh();
        $newSnapshot = $request->queryApproval->result_snapshot;
        
        $this->assertNotEquals($originalSnapshot, $newSnapshot);
    }
}
