<?php

use Illuminate\Support\Facades\Route;
use ApprovalWorkflow\ApprovalProcess\Http\Controllers\WorkflowController;
use ApprovalWorkflow\ApprovalProcess\Http\Controllers\ApprovalRequestController;
use ApprovalWorkflow\ApprovalProcess\Http\Controllers\ApprovalActionController;
use ApprovalWorkflow\ApprovalProcess\Http\Controllers\DelegationController;
use ApprovalWorkflow\ApprovalProcess\Http\Controllers\DashboardController;

Route::prefix(config('approval-process.paths.api_prefix'))
    ->middleware(['api', 'auth:api'])
    ->group(function () {
        // Workflow routes
        Route::apiResource('workflows', WorkflowController::class);
        Route::post('workflows/{workflow}/clone', [WorkflowController::class, 'clone']);
        Route::patch('workflows/{workflow}/enable', [WorkflowController::class, 'enable']);
        Route::patch('workflows/{workflow}/disable', [WorkflowController::class, 'disable']);

        // Approval request routes
        Route::apiResource('requests', ApprovalRequestController::class);
        Route::post('requests/{request}/submit', [ApprovalRequestController::class, 'submit']);
        Route::post('requests/{request}/approve', [ApprovalRequestController::class, 'approve']);
        Route::post('requests/{request}/reject', [ApprovalRequestController::class, 'reject']);
        Route::post('requests/{request}/send-back', [ApprovalRequestController::class, 'sendBack']);
        Route::post('requests/{request}/hold', [ApprovalRequestController::class, 'hold']);
        Route::post('requests/{request}/cancel', [ApprovalRequestController::class, 'cancel']);
        Route::post('requests/{request}/resubmit', [ApprovalRequestController::class, 'resubmit']);

        // Approval action routes
        Route::get('requests/{request}/actions', [ApprovalActionController::class, 'index']);
        Route::get('requests/{request}/history', [ApprovalActionController::class, 'history']);

        // Delegation routes
        Route::apiResource('delegations', DelegationController::class);
        Route::patch('delegations/{delegation}/activate', [DelegationController::class, 'activate']);
        Route::patch('delegations/{delegation}/deactivate', [DelegationController::class, 'deactivate']);

        // Dashboard routes
        Route::get('dashboard/stats', [DashboardController::class, 'stats']);
        Route::get('dashboard/pending', [DashboardController::class, 'pending']);
        Route::get('dashboard/approved', [DashboardController::class, 'approved']);
        Route::get('dashboard/rejected', [DashboardController::class, 'rejected']);
    });
