<?php

namespace AshiqFardus\ApprovalProcess\Tests\Unit\Services;

use AshiqFardus\ApprovalProcess\Tests\TestCase;
use AshiqFardus\ApprovalProcess\Services\DocumentTemplateService;
use AshiqFardus\ApprovalProcess\Models\DocumentTemplate;
use AshiqFardus\ApprovalProcess\Models\Workflow;
use AshiqFardus\ApprovalProcess\Models\ApprovalStep;
use AshiqFardus\ApprovalProcess\Models\ApprovalRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

class DocumentTemplateServiceTest extends TestCase
{
    use RefreshDatabase;

    protected DocumentTemplateService $service;
    protected $user;
    protected ApprovalRequest $request;

    protected function setUp(): void
    {
        parent::setUp();
        
        Storage::fake('local');
        
        $this->service = new DocumentTemplateService();
        $this->user = $this->createUser();
        
        $workflow = Workflow::create([
            'name' => 'Test Workflow',
            'model_type' => 'App\\Models\\Test',
            'is_active' => true,
        ]);

        $step = ApprovalStep::create([
            'workflow_id' => $workflow->id,
            'name' => 'Step 1',
            'sequence' => 1,
            'approval_type' => 'serial',
        ]);

        $this->request = ApprovalRequest::create([
            'workflow_id' => $workflow->id,
            'requestable_type' => 'App\\Models\\Test',
            'requestable_id' => 1,
            'requested_by_user_id' => $this->user->id,
            'status' => 'submitted',
            'current_step_id' => $step->id,
        ]);
    }

    /** @test */
    public function it_creates_a_template()
    {
        $template = $this->service->createTemplate([
            'name' => 'Test Template',
            'code' => 'test_template',
            'content' => 'Hello {{name}}, your request {{request_id}} is {{status}}.',
            'file_type' => 'html',
            'category' => 'letter',
        ], $this->user->id);

        $this->assertInstanceOf(DocumentTemplate::class, $template);
        $this->assertEquals('Test Template', $template->name);
        $this->assertDatabaseHas('document_templates', [
            'code' => 'test_template',
        ]);
    }

    /** @test */
    public function it_extracts_variables_from_template()
    {
        $template = DocumentTemplate::create([
            'name' => 'Test',
            'code' => 'test',
            'content' => 'Hello {{name}}, amount: {{amount}}',
            'file_type' => 'html',
        ]);

        $variables = $template->extractVariables();

        $this->assertContains('name', $variables);
        $this->assertContains('amount', $variables);
    }

    /** @test */
    public function it_validates_template_data()
    {
        $template = DocumentTemplate::create([
            'name' => 'Test',
            'code' => 'test',
            'content' => 'Hello {{name}}',
            'file_type' => 'html',
            'variables' => [
                'required' => ['name', 'amount'],
            ],
        ]);

        $errors = $template->validateData(['name' => 'John']);

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('amount', $errors[0]);
    }

    /** @test */
    public function it_renders_template_with_data()
    {
        $template = DocumentTemplate::create([
            'name' => 'Test',
            'code' => 'test',
            'content' => 'Hello {{name}}, your total is {{amount}}',
            'file_type' => 'html',
        ]);

        $rendered = $template->render([
            'name' => 'John',
            'amount' => '$100',
        ]);

        $this->assertEquals('Hello John, your total is $100', $rendered);
    }

    /** @test */
    public function it_generates_document_from_template()
    {
        $template = DocumentTemplate::create([
            'name' => 'Test',
            'code' => 'test',
            'content' => 'Hello {{name}}',
            'file_type' => 'html',
            'variables' => [
                'required' => ['name'],
            ],
        ]);

        $document = $this->service->generateDocument(
            $template,
            $this->request,
            ['name' => 'John'],
            $this->user->id
        );

        $this->assertDatabaseHas('generated_documents', [
            'approval_request_id' => $this->request->id,
            'template_id' => $template->id,
        ]);
    }

    /** @test */
    public function it_clones_a_template()
    {
        $template = DocumentTemplate::create([
            'name' => 'Original',
            'code' => 'original',
            'content' => 'Content',
            'file_type' => 'html',
        ]);

        $cloned = $this->service->cloneTemplate($template, $this->user->id, 'Cloned');

        $this->assertEquals('Cloned', $cloned->name);
        $this->assertNotEquals($template->code, $cloned->code);
        $this->assertEquals($template->content, $cloned->content);
    }

    /** @test */
    public function it_validates_template_syntax()
    {
        $content = 'Hello {{name}}, {{undefined_var}}';
        $variables = ['available' => ['name']];

        $errors = $this->service->validateTemplate($content, $variables);

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('undefined_var', $errors[0]);
    }
}
