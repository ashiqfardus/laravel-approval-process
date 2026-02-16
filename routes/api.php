<?php

use Illuminate\Support\Facades\Route;
use AshiqFardus\ApprovalProcess\Http\Controllers\WorkflowController;
use AshiqFardus\ApprovalProcess\Http\Controllers\ApprovalRequestController;
use AshiqFardus\ApprovalProcess\Http\Controllers\ApprovalActionController;
use AshiqFardus\ApprovalProcess\Http\Controllers\DelegationController;
use AshiqFardus\ApprovalProcess\Http\Controllers\DashboardController;
use AshiqFardus\ApprovalProcess\Http\Controllers\NotificationController;

Route::prefix(config('approval-process.paths.api_prefix'))
    ->middleware(['api', 'auth:api'])
    ->group(function () {
        // Workflow routes
        Route::apiResource('workflows', WorkflowController::class);
        Route::post('workflows/{workflow}/clone', [WorkflowController::class, 'clone']);
        Route::patch('workflows/{workflow}/enable', [WorkflowController::class, 'enable']);
        Route::patch('workflows/{workflow}/disable', [WorkflowController::class, 'disable']);
        
        // Workflow step management routes
        Route::get('workflows/{workflow}/steps', [WorkflowController::class, 'steps']);
        Route::post('workflows/{workflow}/steps', [WorkflowController::class, 'addStep'])->where('workflow', '[0-9]+');
        Route::put('workflows/{workflow}/steps/{step}', [WorkflowController::class, 'updateStep']);
        Route::delete('workflows/{workflow}/steps/{step}', [WorkflowController::class, 'removeStep']);
        Route::post('workflows/{workflow}/steps/reorder', [WorkflowController::class, 'reorderSteps']);

        // Approval request routes
        Route::apiResource('requests', ApprovalRequestController::class);
        Route::post('requests/{approval_request}/submit', [ApprovalRequestController::class, 'submit']);
        Route::post('requests/{approval_request}/approve', [ApprovalRequestController::class, 'approve']);
        Route::post('requests/{approval_request}/reject', [ApprovalRequestController::class, 'reject']);
        Route::post('requests/{approval_request}/send-back', [ApprovalRequestController::class, 'sendBack']);
        Route::post('requests/{approval_request}/hold', [ApprovalRequestController::class, 'hold']);
        Route::post('requests/{approval_request}/cancel', [ApprovalRequestController::class, 'cancel']);
        Route::post('requests/{approval_request}/resubmit', [ApprovalRequestController::class, 'resubmit']);
        Route::post('requests/{approval_request}/edit-and-resubmit', [ApprovalRequestController::class, 'editAndResubmit']);
        Route::get('requests/{approval_request}/change-history', [ApprovalRequestController::class, 'changeHistory']);

        // Approval action routes
        Route::get('requests/{request}/actions', [ApprovalActionController::class, 'index']);
        Route::get('requests/{request}/history', [ApprovalActionController::class, 'history']);

        // Delegation routes
        Route::apiResource('delegations', DelegationController::class);
        Route::patch('delegations/{delegation}/activate', [DelegationController::class, 'activate']);
        Route::patch('delegations/{delegation}/deactivate', [DelegationController::class, 'deactivate']);

        // Notification routes
        Route::get('notifications', [NotificationController::class, 'index']);
        Route::get('notifications/unread', [NotificationController::class, 'unread']);
        Route::get('notifications/count', [NotificationController::class, 'count']);
        Route::post('notifications/{id}/read', [NotificationController::class, 'markAsRead']);
        Route::post('notifications/read-all', [NotificationController::class, 'markAllAsRead']);

        // Dashboard routes
        Route::get('dashboard/stats', [DashboardController::class, 'stats']);
        Route::get('dashboard/pending', [DashboardController::class, 'pending']);
        Route::get('dashboard/approved', [DashboardController::class, 'approved']);
        Route::get('dashboard/rejected', [DashboardController::class, 'rejected']);
    });
