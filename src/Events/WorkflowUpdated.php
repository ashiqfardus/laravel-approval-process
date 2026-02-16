<?php

namespace AshiqFardus\ApprovalProcess\Events;

use AshiqFardus\ApprovalProcess\Models\Workflow;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WorkflowUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Workflow $workflow;
    public string $changeType;

    /**
     * Create a new event instance.
     */
    public function __construct(Workflow $workflow, string $changeType)
    {
        $this->workflow = $workflow;
        $this->changeType = $changeType;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('workflows'),
            new Channel('workflow.' . $this->workflow->id),
        ];
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'workflow_id' => $this->workflow->id,
            'workflow_name' => $this->workflow->name,
            'change_type' => $this->changeType,
            'is_active' => $this->workflow->is_active,
            'timestamp' => now()->toIso8601String(),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'workflow.updated';
    }
}
