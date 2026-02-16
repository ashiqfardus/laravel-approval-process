<?php

namespace AshiqFardus\ApprovalProcess\Events;

use AshiqFardus\ApprovalProcess\Models\ApprovalRequest;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ApprovalRequestUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public ApprovalRequest $request;
    public string $action;
    public ?int $userId;

    /**
     * Create a new event instance.
     */
    public function __construct(ApprovalRequest $request, string $action, ?int $userId = null)
    {
        $this->request = $request;
        $this->action = $action;
        $this->userId = $userId;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('approval-requests'),
            new Channel('approval-request.' . $this->request->id),
            new PresenceChannel('workflow.' . $this->request->workflow_id),
        ];
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'request_id' => $this->request->id,
            'workflow_id' => $this->request->workflow_id,
            'status' => $this->request->status,
            'action' => $this->action,
            'user_id' => $this->userId,
            'current_step_id' => $this->request->current_step_id,
            'timestamp' => now()->toIso8601String(),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'approval.request.updated';
    }
}
