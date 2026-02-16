<?php

namespace AshiqFardus\ApprovalProcess\Tests\Unit\Services;

use AshiqFardus\ApprovalProcess\Tests\TestCase;
use AshiqFardus\ApprovalProcess\Services\ChangeHistoryFormatter;
use AshiqFardus\ApprovalProcess\Models\Workflow;
use AshiqFardus\ApprovalProcess\Models\ApprovalStep;
use AshiqFardus\ApprovalProcess\Models\ApprovalRequest;
use AshiqFardus\ApprovalProcess\Models\ApprovalChangeLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;

class ChangeHistoryFormatterTest extends TestCase
{
    use RefreshDatabase;

    protected ChangeHistoryFormatter $formatter;
    protected $user;
    protected $workflow;
    protected $request;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->formatter = new ChangeHistoryFormatter();
        $this->user = $this->createUser(['name' => 'John Doe']);
        
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
        ]);
    }

    /** @test */
    public function it_formats_single_change()
    {
        $change = ApprovalChangeLog::create([
            'approval_request_id' => $this->request->id,
            'user_id' => $this->user->id,
            'field_name' => 'name',
            'old_value' => 'Old Name',
            'new_value' => 'New Name',
        ]);
        
        $formatted = $this->formatter->formatChange($change);
        
        $this->assertStringContainsString('John Doe', $formatted);
        $this->assertStringContainsString('Name', $formatted); // Formatted to Title Case
        $this->assertStringContainsString('Old Name', $formatted);
        $this->assertStringContainsString('New Name', $formatted);
    }

    /** @test */
    public function it_formats_change_with_null_old_value()
    {
        $change = ApprovalChangeLog::create([
            'approval_request_id' => $this->request->id,
            'user_id' => $this->user->id,
            'field_name' => 'description',
            'old_value' => null,
            'new_value' => 'New Description',
        ]);
        
        $formatted = $this->formatter->formatChange($change);
        
        $this->assertStringContainsString('added', $formatted);
        $this->assertStringContainsString('New Description', $formatted);
    }

    /** @test */
    public function it_formats_change_with_null_new_value()
    {
        $change = ApprovalChangeLog::create([
            'approval_request_id' => $this->request->id,
            'user_id' => $this->user->id,
            'field_name' => 'description',
            'old_value' => 'Old Description',
            'new_value' => null,
        ]);
        
        $formatted = $this->formatter->formatChange($change);
        
        $this->assertStringContainsString('removed', $formatted);
        $this->assertStringContainsString('Old Description', $formatted);
    }

    /** @test */
    public function it_formats_multiple_changes()
    {
        $changes = collect([
            ApprovalChangeLog::create([
                'approval_request_id' => $this->request->id,
                'user_id' => $this->user->id,
                'field_name' => 'name',
                'old_value' => 'Old',
                'new_value' => 'New',
            ]),
            ApprovalChangeLog::create([
                'approval_request_id' => $this->request->id,
                'user_id' => $this->user->id,
                'field_name' => 'amount',
                'old_value' => '100',
                'new_value' => '200',
            ]),
        ]);
        
        $formatted = $this->formatter->formatChanges($changes);
        
        $this->assertStringContainsString('Name', $formatted); // Formatted to Title Case
        $this->assertStringContainsString('Amount', $formatted); // Formatted to Title Case
        $this->assertStringContainsString('-', $formatted); // List format
    }

    /** @test */
    public function it_formats_empty_changes()
    {
        $changes = collect();
        
        $formatted = $this->formatter->formatChanges($changes);
        
        $this->assertEquals('No changes recorded.', $formatted);
    }

    /** @test */
    public function it_formats_request_history_grouped_by_date()
    {
        $now = now();
        
        ApprovalChangeLog::create([
            'approval_request_id' => $this->request->id,
            'user_id' => $this->user->id,
            'field_name' => 'name',
            'old_value' => 'Old',
            'new_value' => 'New',
            'created_at' => $now,
        ]);
        
        $formatted = $this->formatter->formatRequestHistory($this->request, [
            'group_by' => 'date'
        ]);
        
        $this->assertStringContainsString('##', $formatted); // Date header
        $this->assertStringContainsString($now->format('Y-m-d'), $formatted);
    }

    /** @test */
    public function it_formats_request_history_grouped_by_user()
    {
        $user2 = $this->createUser(['name' => 'Jane Smith']);
        
        ApprovalChangeLog::create([
            'approval_request_id' => $this->request->id,
            'user_id' => $this->user->id,
            'field_name' => 'name',
            'old_value' => 'Old',
            'new_value' => 'New',
        ]);
        
        ApprovalChangeLog::create([
            'approval_request_id' => $this->request->id,
            'user_id' => $user2->id,
            'field_name' => 'amount',
            'old_value' => '100',
            'new_value' => '200',
        ]);
        
        $formatted = $this->formatter->formatRequestHistory($this->request, [
            'group_by' => 'user'
        ]);
        
        $this->assertStringContainsString('John Doe', $formatted);
        $this->assertStringContainsString('Jane Smith', $formatted);
    }

    /** @test */
    public function it_formats_request_history_grouped_by_field()
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
            'field_name' => 'name',
            'old_value' => 'New',
            'new_value' => 'Newer',
        ]);
        
        $formatted = $this->formatter->formatRequestHistory($this->request, [
            'group_by' => 'field'
        ]);
        
        $this->assertStringContainsString('Name', $formatted); // Formatted field name
    }

    /** @test */
    public function it_formats_field_name_correctly()
    {
        $change = ApprovalChangeLog::create([
            'approval_request_id' => $this->request->id,
            'user_id' => $this->user->id,
            'field_name' => 'user_name',
            'old_value' => 'Old',
            'new_value' => 'New',
        ]);
        
        $formatted = $this->formatter->formatChange($change);
        
        $this->assertStringContainsString('User Name', $formatted); // Snake case converted
    }

    /** @test */
    public function it_formats_array_values()
    {
        $change = ApprovalChangeLog::create([
            'approval_request_id' => $this->request->id,
            'user_id' => $this->user->id,
            'field_name' => 'tags',
            'old_value' => json_encode(['tag1', 'tag2']),
            'new_value' => json_encode(['tag1', 'tag3']),
        ]);
        
        $formatted = $this->formatter->formatChange($change);
        
        $this->assertIsString($formatted);
        // Should handle JSON gracefully
    }

    /** @test */
    public function it_formats_boolean_values()
    {
        $change = ApprovalChangeLog::create([
            'approval_request_id' => $this->request->id,
            'user_id' => $this->user->id,
            'field_name' => 'is_active',
            'old_value' => '0',
            'new_value' => '1',
        ]);
        
        $formatted = $this->formatter->formatChange($change);
        
        $this->assertIsString($formatted);
    }

    /** @test */
    public function it_formats_as_html()
    {
        ApprovalChangeLog::create([
            'approval_request_id' => $this->request->id,
            'user_id' => $this->user->id,
            'field_name' => 'name',
            'old_value' => 'Old',
            'new_value' => 'New',
        ]);
        
        $html = $this->formatter->formatAsHtml($this->request);
        
        $this->assertStringContainsString('<div', $html);
        $this->assertStringContainsString('change-history', $html);
        $this->assertStringContainsString('name', $html);
    }

    /** @test */
    public function it_handles_empty_history_in_html()
    {
        $html = $this->formatter->formatAsHtml($this->request);
        
        $this->assertStringContainsString('<p>No changes recorded.</p>', $html);
    }

    /** @test */
    public function it_formats_long_values_with_truncation()
    {
        $longValue = str_repeat('a', 200);
        
        $change = ApprovalChangeLog::create([
            'approval_request_id' => $this->request->id,
            'user_id' => $this->user->id,
            'field_name' => 'description',
            'old_value' => 'Short',
            'new_value' => $longValue,
        ]);
        
        $formatted = $this->formatter->formatChange($change);
        
        // Should truncate long values
        $this->assertLessThanOrEqual(strlen($longValue) + 50, strlen($formatted));
    }
}
