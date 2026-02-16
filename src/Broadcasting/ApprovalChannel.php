<?php

namespace AshiqFardus\ApprovalProcess\Broadcasting;

use AshiqFardus\ApprovalProcess\Models\ApprovalRequest;
use Illuminate\Foundation\Auth\User;

class ApprovalChannel
{
    /**
     * Authenticate the user's access to the channel.
     */
    public function join(User $user, int $requestId): array|bool
    {
        $request = ApprovalRequest::find($requestId);

        if (!$request) {
            return false;
        }

        // User can join if they are:
        // 1. The requester
        // 2. An approver in the current step
        // 3. An admin (if you have role checking)
        
        if ($request->requested_by_user_id === $user->id) {
            return ['id' => $user->id, 'name' => $user->name, 'role' => 'requester'];
        }

        // Check if user is an approver
        $isApprover = $request->currentStep?->approvers()
            ->where('approver_type', 'user')
            ->where('approver_id', $user->id)
            ->exists();

        if ($isApprover) {
            return ['id' => $user->id, 'name' => $user->name, 'role' => 'approver'];
        }

        return false;
    }
}
