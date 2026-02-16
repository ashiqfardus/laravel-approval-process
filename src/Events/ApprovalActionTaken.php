<?php

namespace AshiqFardus\ApprovalProcess\Events;

use AshiqFardus\ApprovalProcess\Models\ApprovalAction;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ApprovalActionTaken implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public ApprovalAction $action;

    /**
     * Create a new event instance.
     */
    public function __construct(ApprovalAction $action)
    {
        $this->action = $action;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('approval-request.' . $this->action->approval_request_id),
            new Channel('user.' . $this->action->user_id),
        ];
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'action_id' => $this->action->id,
            'request_id' => $this->action->approval_request_id,
            'step_id' => $this->action->approval_step_id,
            'user_id' => $this->action->user_id,
            'action_type' => $this->action->action_type,
            'comments' => $this->action->comments,
            'timestamp' => $this->action->created_at->toIso8601String(),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'approval.action.taken';
    }
}
