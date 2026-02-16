<?php

namespace AshiqFardus\ApprovalProcess\Tests\Feature;

use AshiqFardus\ApprovalProcess\Tests\TestCase;
use AshiqFardus\ApprovalProcess\Models\Workflow;
use AshiqFardus\ApprovalProcess\Models\ApprovalStep;
use AshiqFardus\ApprovalProcess\Models\ApprovalRequest;
use AshiqFardus\ApprovalProcess\Models\DocumentTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class DocumentManagementTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $workflow;
    protected $step;
    protected $request;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->withoutMiddleware();
        Storage::fake('local');
        
        $this->user = $this->createUser();
        
        $this->workflow = Workflow::create([
            'name' => 'Test Workflow',
            'model_type' => 'App\\Models\\Document',
            'is_active' => true,
        ]);

        $this->step = ApprovalStep::create([
            'workflow_id' => $this->workflow->id,
            'name' => 'Manager Approval',
            'sequence' => 1,
            'approval_type' => 'serial',
        ]);

        $this->request = ApprovalRequest::create([
            'workflow_id' => $this->workflow->id,
            'requestable_type' => 'App\\Models\\Document',
            'requestable_id' => 1,
            'requested_by_user_id' => $this->user->id,
            'status' => 'submitted',
            'current_step_id' => $this->step->id,
        ]);
    }

    /** @test */
    public function it_can_upload_an_attachment()
    {
        $file = UploadedFile::fake()->create('document.pdf', 100);

        $response = $this->postJson("/api/approval-process/requests/{$this->request->id}/attachments", [
            'file' => $file,
            'type' => 'supporting_document',
            'description' => 'Test document',
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure(['message', 'attachment']);
        
        $this->assertDatabaseHas('approval_attachments', [
            'approval_request_id' => $this->request->id,
            'original_file_name' => 'document.pdf',
        ]);
    }

    /** @test */
    public function it_can_get_all_attachments_for_request()
    {
        $file = UploadedFile::fake()->create('document.pdf', 100);
        
        $this->postJson("/api/approval-process/requests/{$this->request->id}/attachments", [
            'file' => $file,
        ]);

        $response = $this->getJson("/api/approval-process/requests/{$this->request->id}/attachments");

        $response->assertStatus(200);
        $response->assertJsonCount(1);
    }

    /** @test */
    public function it_can_get_attachment_statistics()
    {
        $file = UploadedFile::fake()->create('document.pdf', 100);
        
        $this->postJson("/api/approval-process/requests/{$this->request->id}/attachments", [
            'file' => $file,
        ]);

        $response = $this->getJson("/api/approval-process/requests/{$this->request->id}/attachments-stats");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'total_count',
            'total_size',
            'by_type',
            'by_extension',
        ]);
    }

    /** @test */
    public function it_can_create_a_template()
    {
        $response = $this->postJson('/api/approval-process/templates', [
            'name' => 'Test Template',
            'code' => 'test_template',
            'content' => 'Hello {{name}}',
            'file_type' => 'html',
            'category' => 'letter',
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure(['message', 'template']);
        
        $this->assertDatabaseHas('document_templates', [
            'code' => 'test_template',
        ]);
    }

    /** @test */
    public function it_can_get_all_templates()
    {
        DocumentTemplate::create([
            'name' => 'Template 1',
            'code' => 'template1',
            'content' => 'Content',
            'file_type' => 'html',
        ]);

        $response = $this->getJson('/api/approval-process/templates');

        $response->assertStatus(200);
        $response->assertJsonCount(1);
    }

    /** @test */
    public function it_can_generate_document_from_template()
    {
        $template = DocumentTemplate::create([
            'name' => 'Test Template',
            'code' => 'test',
            'content' => 'Hello {{name}}',
            'file_type' => 'html',
            'variables' => [
                'required' => ['name'],
            ],
        ]);

        $response = $this->postJson("/api/approval-process/templates/{$template->id}/generate", [
            'approval_request_id' => $this->request->id,
            'data' => [
                'name' => 'John',
            ],
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure(['message', 'document']);
    }

    /** @test */
    public function it_can_preview_template()
    {
        $template = DocumentTemplate::create([
            'name' => 'Test',
            'code' => 'test',
            'content' => 'Hello {{name}}',
            'file_type' => 'html',
        ]);

        $response = $this->postJson("/api/approval-process/templates/{$template->id}/preview", [
            'data' => ['name' => 'John'],
        ]);

        $response->assertStatus(200);
        $response->assertJson(['preview' => 'Hello John']);
    }

    /** @test */
    public function it_can_clone_a_template()
    {
        $template = DocumentTemplate::create([
            'name' => 'Original',
            'code' => 'original',
            'content' => 'Content',
            'file_type' => 'html',
        ]);

        $response = $this->postJson("/api/approval-process/templates/{$template->id}/clone", [
            'name' => 'Cloned Template',
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('template.name', 'Cloned Template');
    }

    /** @test */
    public function it_can_create_a_signature()
    {
        $response = $this->postJson("/api/approval-process/requests/{$this->request->id}/signatures", [
            'signature_type' => 'typed',
            'signature_data' => 'John Doe',
            'format' => 'png',
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure(['message', 'signature']);
        
        $this->assertDatabaseHas('approval_signatures', [
            'approval_request_id' => $this->request->id,
            'signature_type' => 'typed',
        ]);
    }

    /** @test */
    public function it_can_get_all_signatures_for_request()
    {
        $this->postJson("/api/approval-process/requests/{$this->request->id}/signatures", [
            'signature_type' => 'typed',
            'signature_data' => 'John Doe',
        ]);

        $response = $this->getJson("/api/approval-process/requests/{$this->request->id}/signatures");

        $response->assertStatus(200);
        $response->assertJsonCount(1);
    }

    /** @test */
    public function it_can_get_signature_statistics()
    {
        $this->postJson("/api/approval-process/requests/{$this->request->id}/signatures", [
            'signature_type' => 'typed',
            'signature_data' => 'John Doe',
        ]);

        $response = $this->getJson("/api/approval-process/requests/{$this->request->id}/signatures-stats");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'total_count',
            'verified_count',
            'by_type',
        ]);
    }

    /** @test */
    public function it_can_get_signature_types()
    {
        $response = $this->getJson('/api/approval-process/signatures/types');

        $response->assertStatus(200);
        $response->assertJsonStructure(['signature_types']);
    }
}
