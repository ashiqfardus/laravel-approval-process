<?php

namespace AshiqFardus\ApprovalProcess\Tests\Unit\Services;

use AshiqFardus\ApprovalProcess\Tests\TestCase;
use AshiqFardus\ApprovalProcess\Services\ApprovalNotificationService;
use AshiqFardus\ApprovalProcess\Models\Workflow;
use AshiqFardus\ApprovalProcess\Models\ApprovalStep;
use AshiqFardus\ApprovalProcess\Models\ApprovalRequest;
use AshiqFardus\ApprovalProcess\Models\ApprovalApprover;
use AshiqFardus\ApprovalProcess\Models\ApprovalNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ApprovalNotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ApprovalNotificationService $service;
    protected $user;
    protected $workflow;
    protected $request;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = new ApprovalNotificationService();
        $this->user = $this->createUser();
        
        // Create workflow and request
        $this->workflow = Workflow::create([
            'name' => 'Test Workflow',
            'model_type' => 'App\\Models\\TestModel',
            'is_active' => true,
        ]);
        
        $step = ApprovalStep::create([
            'workflow_id' => $this->workflow->id,
            'name' => 'Level 1 Approval',
            'sequence' => 1,
            'approval_type' => 'serial',
        ]);
        
        ApprovalApprover::create([
            'approval_step_id' => $step->id,
            'user_id' => $this->user->id,
        ]);
        
        $this->request = ApprovalRequest::create([
            'workflow_id' => $this->workflow->id,
            'requestable_type' => 'App\\Models\\TestModel',
            'requestable_id' => 1,
            'requested_by_user_id' => $this->user->id,
            'current_step_id' => $step->id,
            'status' => 'pending',
        ]);
    }

    /** @test */
    public function it_can_notify_approvers()
    {
        $this->service->notifyApprovers($this->request, 'pending');

        $this->assertDatabaseHas('approval_notifications', [
            'approval_request_id' => $this->request->id,
            'user_id' => $this->user->id,
            'type' => 'pending',
        ]);
    }

    /** @test */
    public function it_can_notify_approvers_with_multiple_channels()
    {
        $this->service->notifyApprovers($this->request, 'pending', null, ['email', 'sms', 'real-time']);

        $notification = ApprovalNotification::where('approval_request_id', $this->request->id)->first();

        $this->assertNotNull($notification);
        $this->assertContains('email', $notification->channels);
        $this->assertContains('sms', $notification->channels);
        $this->assertContains('real-time', $notification->channels);
    }

    /** @test */
    public function it_can_notify_creator()
    {
        $this->service->notifyCreator($this->request, 'approved', 'Your request has been approved');

        $this->assertDatabaseHas('approval_notifications', [
            'approval_request_id' => $this->request->id,
            'user_id' => $this->user->id,
            'type' => 'approved',
        ]);
    }

    /** @test */
    public function it_can_get_unread_notifications()
    {
        ApprovalNotification::create([
            'approval_request_id' => $this->request->id,
            'user_id' => $this->user->id,
            'type' => 'pending',
            'message' => 'Test notification',
            'channels' => ['real-time'],
            'is_read' => false,
        ]);

        $unread = $this->service->getUnreadNotifications($this->user->id);

        $this->assertCount(1, $unread);
    }

    /** @test */
    public function it_can_mark_notification_as_read()
    {
        $notification = ApprovalNotification::create([
            'approval_request_id' => $this->request->id,
            'user_id' => $this->user->id,
            'type' => 'pending',
            'message' => 'Test notification',
            'channels' => ['real-time'],
            'is_read' => false,
        ]);

        $this->service->markAsRead($notification->id);

        $this->assertDatabaseHas('approval_notifications', [
            'id' => $notification->id,
            'is_read' => true,
        ]);
    }

    /** @test */
    public function it_can_mark_all_as_read()
    {
        ApprovalNotification::create([
            'approval_request_id' => $this->request->id,
            'user_id' => $this->user->id,
            'type' => 'pending',
            'message' => 'Test 1',
            'channels' => ['real-time'],
            'is_read' => false,
        ]);

        ApprovalNotification::create([
            'approval_request_id' => $this->request->id,
            'user_id' => $this->user->id,
            'type' => 'reminder',
            'message' => 'Test 2',
            'channels' => ['real-time'],
            'is_read' => false,
        ]);

        $this->service->markAllAsRead($this->user->id);

        $unread = ApprovalNotification::where('user_id', $this->user->id)
            ->where('is_read', false)
            ->count();

        $this->assertEquals(0, $unread);
    }

    /** @test */
    public function it_can_send_reminder()
    {
        $this->service->sendReminder($this->request);

        $this->assertDatabaseHas('approval_notifications', [
            'approval_request_id' => $this->request->id,
            'type' => 'reminder',
        ]);

        $this->assertNotNull($this->request->fresh()->last_reminder_sent);
    }
}
