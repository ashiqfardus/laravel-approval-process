<?php

use Illuminate\Support\Facades\Broadcast;
use AshiqFardus\ApprovalProcess\Broadcasting\ApprovalChannel;
use AshiqFardus\ApprovalProcess\Broadcasting\WorkflowChannel;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('approval-request.{requestId}', ApprovalChannel::class);
Broadcast::channel('workflow.{workflowId}', WorkflowChannel::class);

Broadcast::channel('user.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

Broadcast::channel('approval-requests', function ($user) {
    // All authenticated users can listen to general approval request updates
    return true;
});

Broadcast::channel('workflows', function ($user) {
    // All authenticated users can listen to workflow updates
    return true;
});
