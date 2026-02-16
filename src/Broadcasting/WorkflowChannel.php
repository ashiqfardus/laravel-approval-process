<?php

namespace AshiqFardus\ApprovalProcess\Broadcasting;

use AshiqFardus\ApprovalProcess\Models\Workflow;
use Illuminate\Foundation\Auth\User;

class WorkflowChannel
{
    /**
     * Authenticate the user's access to the channel.
     */
    public function join(User $user, int $workflowId): array|bool
    {
        $workflow = Workflow::find($workflowId);

        if (!$workflow) {
            return false;
        }

        // All authenticated users can join workflow channels
        // You can add more specific authorization logic here
        
        return [
            'id' => $user->id,
            'name' => $user->name,
        ];
    }
}
