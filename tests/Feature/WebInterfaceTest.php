<?php

namespace AshiqFardus\ApprovalProcess\Tests\Feature;

use AshiqFardus\ApprovalProcess\Models\Workflow;
use AshiqFardus\ApprovalProcess\Models\ApprovalRequest;
use AshiqFardus\ApprovalProcess\Models\ApprovalStep;
use AshiqFardus\ApprovalProcess\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Auth\User;

class WebInterfaceTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
    }

    /** @test */
    public function it_has_dashboard_route()
    {
        $this->assertTrue(route('approval-process.dashboard') !== null);
    }

    /** @test */
    public function it_has_workflows_routes()
    {
        $this->assertTrue(route('approval-process.workflows.index') !== null);
        $this->assertTrue(route('approval-process.workflows.create') !== null);
    }

    /** @test */
    public function it_has_workflow_designer_route()
    {
        $this->assertTrue(route('approval-process.workflows.designer', 1) !== null);
    }

    /** @test */
    public function it_saves_workflow_design()
    {
        $workflow = Workflow::create([
            'name' => 'Test Workflow',
            'model_type' => 'App\\Models\\TestModel',
            'is_active' => true,
        ]);

        $designData = [
            'design' => [
                'name' => 'Updated Workflow',
                'description' => 'Updated description',
                'steps' => [],
            ],
        ];

        $response = $this->postJson(route('approval-process.workflows.designer.save', $workflow->id), $designData);

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Workflow saved successfully']);
        
        $this->assertDatabaseHas('approval_workflows', [
            'id' => $workflow->id,
            'name' => 'Updated Workflow',
        ]);
    }

    /** @test */
    public function it_exports_workflow_design()
    {
        $workflow = Workflow::create([
            'name' => 'Test Workflow',
            'model_type' => 'App\\Models\\TestModel',
            'is_active' => true,
        ]);

        ApprovalStep::create([
            'workflow_id' => $workflow->id,
            'name' => 'Step 1',
            'sequence' => 1,
        ]);

        $response = $this->getJson(route('approval-process.workflows.designer.export', $workflow->id));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'workflow',
            'steps',
            'exported_at',
            'version',
        ]);
    }

    /** @test */
    public function it_imports_workflow_design()
    {
        $workflowData = [
            'workflow_data' => [
                'workflow' => [
                    'name' => 'Imported Workflow',
                    'model_type' => 'App\\Models\\TestModel',
                    'description' => 'Imported from file',
                ],
                'steps' => [
                    [
                        'name' => 'Imported Step',
                        'sequence' => 1,
                        'description' => 'Test step',
                    ],
                ],
            ],
        ];

        $response = $this->postJson(route('approval-process.workflows.designer.import'), $workflowData);

        $response->assertStatus(201);
        $response->assertJson(['message' => 'Workflow imported successfully']);
        
        $this->assertDatabaseHas('approval_workflows', [
            'name' => 'Imported Workflow (Imported)',
        ]);
    }

    /** @test */
    public function it_has_request_routes()
    {
        $this->assertTrue(route('approval-process.requests.index') !== null);
        $this->assertTrue(route('approval-process.requests.create') !== null);
    }

    /** @test */
    public function it_has_request_detail_route()
    {
        $this->assertTrue(route('approval-process.requests.show', 1) !== null);
    }

    /** @test */
    public function it_has_my_approvals_route()
    {
        $this->assertTrue(route('approval-process.my-approvals') !== null);
    }

    /** @test */
    public function it_has_my_requests_route()
    {
        $this->assertTrue(route('approval-process.my-requests') !== null);
    }

    /** @test */
    public function it_has_analytics_route()
    {
        $this->assertTrue(route('approval-process.analytics.index') !== null);
    }

    /** @test */
    public function it_has_reports_route()
    {
        $this->assertTrue(route('approval-process.reports.index') !== null);
    }

    /** @test */
    public function it_has_settings_route()
    {
        $this->assertTrue(route('approval-process.settings') !== null);
    }
}
