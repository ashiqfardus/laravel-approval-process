<?php

namespace AshiqFardus\ApprovalProcess\Tests\Unit\Services;

use AshiqFardus\ApprovalProcess\Tests\TestCase;
use AshiqFardus\ApprovalProcess\Services\AttachmentService;
use AshiqFardus\ApprovalProcess\Models\Workflow;
use AshiqFardus\ApprovalProcess\Models\ApprovalStep;
use AshiqFardus\ApprovalProcess\Models\ApprovalRequest;
use AshiqFardus\ApprovalProcess\Models\ApprovalAttachment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class AttachmentServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AttachmentService $service;
    protected $user;
    protected ApprovalRequest $request;

    protected function setUp(): void
    {
        parent::setUp();
        
        Storage::fake('local');
        
        $this->service = new AttachmentService();
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
    public function it_uploads_an_attachment()
    {
        $file = UploadedFile::fake()->create('document.pdf', 100);

        $attachment = $this->service->upload(
            $this->request,
            $file,
            $this->user->id,
            ApprovalAttachment::TYPE_SUPPORTING_DOCUMENT,
            'Test document'
        );

        $this->assertInstanceOf(ApprovalAttachment::class, $attachment);
        $this->assertEquals($this->request->id, $attachment->approval_request_id);
        $this->assertEquals($this->user->id, $attachment->uploaded_by_user_id);
        $this->assertEquals('document.pdf', $attachment->original_file_name);
        $this->assertDatabaseHas('approval_attachments', [
            'id' => $attachment->id,
            'approval_request_id' => $this->request->id,
        ]);
    }

    /** @test */
    public function it_validates_file_upload()
    {
        // Test oversized file
        $largeFile = UploadedFile::fake()->create('large.pdf', 20 * 1024); // 20MB

        $errors = $this->service->validateUpload($largeFile);
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('size', $errors[0]);
    }

    /** @test */
    public function it_gets_request_attachments()
    {
        $file1 = UploadedFile::fake()->create('doc1.pdf', 100);
        $file2 = UploadedFile::fake()->create('doc2.pdf', 100);

        $this->service->upload($this->request, $file1, $this->user->id);
        $this->service->upload($this->request, $file2, $this->user->id);

        $attachments = $this->service->getRequestAttachments($this->request->id);

        $this->assertCount(2, $attachments);
    }

    /** @test */
    public function it_filters_attachments_by_type()
    {
        $file1 = UploadedFile::fake()->create('doc1.pdf', 100);
        $file2 = UploadedFile::fake()->create('sig.png', 50);

        $this->service->upload($this->request, $file1, $this->user->id, ApprovalAttachment::TYPE_SUPPORTING_DOCUMENT);
        $this->service->upload($this->request, $file2, $this->user->id, ApprovalAttachment::TYPE_SIGNATURE);

        $supportingDocs = $this->service->getRequestAttachments($this->request->id, ApprovalAttachment::TYPE_SUPPORTING_DOCUMENT);

        $this->assertCount(1, $supportingDocs);
    }

    /** @test */
    public function it_gets_attachment_statistics()
    {
        $file1 = UploadedFile::fake()->create('doc1.pdf', 100);
        $file2 = UploadedFile::fake()->create('doc2.pdf', 200);

        $this->service->upload($this->request, $file1, $this->user->id);
        $this->service->upload($this->request, $file2, $this->user->id);

        $stats = $this->service->getStatistics($this->request->id);

        $this->assertEquals(2, $stats['total_count']);
        $this->assertGreaterThan(0, $stats['total_size']);
        $this->assertArrayHasKey('by_type', $stats);
    }

    /** @test */
    public function it_deletes_an_attachment()
    {
        $file = UploadedFile::fake()->create('document.pdf', 100);
        $attachment = $this->service->upload($this->request, $file, $this->user->id);

        $result = $this->service->delete($attachment);

        $this->assertTrue($result);
        $this->assertSoftDeleted('approval_attachments', ['id' => $attachment->id]);
    }
}
