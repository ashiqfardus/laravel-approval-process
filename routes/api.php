<?php

use Illuminate\Support\Facades\Route;
use AshiqFardus\ApprovalProcess\Http\Controllers\WorkflowController;
use AshiqFardus\ApprovalProcess\Http\Controllers\WorkflowConditionController;
use AshiqFardus\ApprovalProcess\Http\Controllers\ParallelWorkflowController;
use AshiqFardus\ApprovalProcess\Http\Controllers\DynamicWorkflowController;
use AshiqFardus\ApprovalProcess\Http\Controllers\ApprovalRequestController;
use AshiqFardus\ApprovalProcess\Http\Controllers\ApprovalActionController;
use AshiqFardus\ApprovalProcess\Http\Controllers\DelegationController;
use AshiqFardus\ApprovalProcess\Http\Controllers\DashboardController;
use AshiqFardus\ApprovalProcess\Http\Controllers\NotificationController;
use AshiqFardus\ApprovalProcess\Http\Controllers\AttachmentController;
use AshiqFardus\ApprovalProcess\Http\Controllers\DocumentTemplateController;
use AshiqFardus\ApprovalProcess\Http\Controllers\SignatureController;
use AshiqFardus\ApprovalProcess\Http\Controllers\AnalyticsController;
use AshiqFardus\ApprovalProcess\Http\Controllers\ReportController;
use AshiqFardus\ApprovalProcess\Http\Controllers\Api\WeightageController;
use AshiqFardus\ApprovalProcess\Http\Controllers\Api\EntityController;

Route::prefix(config('approval-process.paths.api_prefix'))
    ->middleware(config('approval-process.api.middleware', ['api', 'auth:sanctum']))
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
        
        // Workflow condition routes (conditional workflows)
        Route::get('workflows/{workflow}/conditions', [WorkflowConditionController::class, 'index']);
        Route::post('workflows/{workflow}/conditions', [WorkflowConditionController::class, 'store']);
        Route::get('workflows/{workflow}/conditions/{condition}', [WorkflowConditionController::class, 'show']);
        Route::put('workflows/{workflow}/conditions/{condition}', [WorkflowConditionController::class, 'update']);
        Route::delete('workflows/{workflow}/conditions/{condition}', [WorkflowConditionController::class, 'destroy']);
        Route::post('workflows/{workflow}/conditions/{condition}/test', [WorkflowConditionController::class, 'test']);
        Route::get('workflows/{workflow}/steps/{step}/possible-next', [WorkflowConditionController::class, 'possibleNextSteps']);
        Route::get('workflows/conditions/operators', [WorkflowConditionController::class, 'operators']);
        
        // Workflow condition groups
        Route::get('workflows/{workflow}/condition-groups', [WorkflowConditionController::class, 'indexGroups']);
        Route::post('workflows/{workflow}/condition-groups', [WorkflowConditionController::class, 'storeGroup']);
        Route::put('workflows/{workflow}/condition-groups/{group}', [WorkflowConditionController::class, 'updateGroup']);
        Route::delete('workflows/{workflow}/condition-groups/{group}', [WorkflowConditionController::class, 'destroyGroup']);
        Route::post('workflows/{workflow}/condition-groups/{group}/test', [WorkflowConditionController::class, 'testGroup']);
        
        // Parallel workflow routes
        Route::get('workflows/{workflow}/parallel-groups', [ParallelWorkflowController::class, 'index']);
        Route::post('workflows/{workflow}/parallel-groups', [ParallelWorkflowController::class, 'store']);
        Route::get('workflows/{workflow}/parallel-groups/{group}', [ParallelWorkflowController::class, 'show']);
        Route::put('workflows/{workflow}/parallel-groups/{group}', [ParallelWorkflowController::class, 'update']);
        Route::delete('workflows/{workflow}/parallel-groups/{group}', [ParallelWorkflowController::class, 'destroy']);
        Route::post('workflows/{workflow}/parallel-groups/{group}/simulate', [ParallelWorkflowController::class, 'simulate']);
        Route::get('workflows/parallel/sync-types', [ParallelWorkflowController::class, 'syncTypes']);
        
        // Parallel execution status
        Route::get('requests/{request}/parallel-status', [ParallelWorkflowController::class, 'requestStatus']);
        Route::get('requests/{request}/parallel-approvers', [ParallelWorkflowController::class, 'pendingApprovers']);
        
        // Dynamic workflow management routes
        Route::post('requests/{request}/add-step', [DynamicWorkflowController::class, 'addStepToRequest']);
        Route::delete('requests/{request}/steps/{step}/remove', [DynamicWorkflowController::class, 'removeStepFromRequest']);
        Route::post('requests/{request}/steps/{step}/skip', [DynamicWorkflowController::class, 'skipStep']);
        Route::post('requests/{request}/steps/{step}/assign-approver', [DynamicWorkflowController::class, 'assignApprover']);
        Route::get('requests/{request}/modifications', [DynamicWorkflowController::class, 'requestModifications']);
        Route::get('requests/{request}/steps/{step}/dynamic-approvers', [DynamicWorkflowController::class, 'stepDynamicApprovers']);
        
        // Workflow versioning routes
        Route::get('workflows/{workflow}/versions', [DynamicWorkflowController::class, 'versionHistory']);
        Route::post('workflows/{workflow}/versions/{version}/rollback', [DynamicWorkflowController::class, 'rollbackVersion']);
        
        // Workflow modification rules
        Route::get('workflows/{workflow}/modification-rules', [DynamicWorkflowController::class, 'modificationRules']);
        Route::post('workflows/{workflow}/modification-rules', [DynamicWorkflowController::class, 'createModificationRule']);
        Route::put('workflows/{workflow}/modification-rules/{rule}', [DynamicWorkflowController::class, 'updateModificationRule']);
        Route::delete('workflows/{workflow}/modification-rules/{rule}', [DynamicWorkflowController::class, 'deleteModificationRule']);
        Route::get('workflows/{workflow}/modification-stats', [DynamicWorkflowController::class, 'modificationStats']);

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

        // Entities & Roles (for dynamic dropdowns)
        Route::get('entities', [EntityController::class, 'index']);
        Route::get('entities/connections', [EntityController::class, 'connections']);
        Route::get('entities/discover', [EntityController::class, 'discover']);
        Route::post('entities', [EntityController::class, 'store']);
        Route::put('entities/{id}', [EntityController::class, 'update']);
        Route::delete('entities/{id}', [EntityController::class, 'destroy']);
        Route::get('roles', [EntityController::class, 'roles']);
        Route::get('users', [EntityController::class, 'users']);


        // Document attachment routes
        Route::get('requests/{request}/attachments', [AttachmentController::class, 'index']);
        Route::post('requests/{request}/attachments', [AttachmentController::class, 'store']);
        Route::get('requests/{request}/attachments/{attachment}', [AttachmentController::class, 'show']);
        Route::get('requests/{request}/attachments/{attachment}/download', [AttachmentController::class, 'download']);
        Route::delete('requests/{request}/attachments/{attachment}', [AttachmentController::class, 'destroy']);
        Route::get('requests/{request}/attachments-stats', [AttachmentController::class, 'statistics']);
        Route::post('requests/{request}/attachments/bulk-download', [AttachmentController::class, 'bulkDownload']);

        // Document template routes
        Route::apiResource('templates', DocumentTemplateController::class);
        Route::post('templates/{template}/generate', [DocumentTemplateController::class, 'generate']);
        Route::post('templates/{template}/preview', [DocumentTemplateController::class, 'preview']);
        Route::post('templates/{template}/clone', [DocumentTemplateController::class, 'clone']);
        Route::get('templates/{template}/statistics', [DocumentTemplateController::class, 'statistics']);
        Route::post('templates/validate', [DocumentTemplateController::class, 'validate']);
        Route::get('templates-categories', [DocumentTemplateController::class, 'categories']);
        Route::get('templates-file-types', [DocumentTemplateController::class, 'fileTypes']);

        // Digital signature routes
        Route::get('requests/{request}/signatures', [SignatureController::class, 'index']);
        Route::post('requests/{request}/signatures', [SignatureController::class, 'store']);
        Route::get('requests/{request}/signatures/{signature}', [SignatureController::class, 'show']);
        Route::post('requests/{request}/signatures/{signature}/verify', [SignatureController::class, 'verify']);
        Route::get('requests/{request}/signatures/{signature}/download', [SignatureController::class, 'downloadImage']);
        Route::get('requests/{request}/signatures-stats', [SignatureController::class, 'statistics']);
        Route::get('signatures/types', [SignatureController::class, 'types']);
        Route::get('signatures/verification-methods', [SignatureController::class, 'verificationMethods']);

        // Analytics routes
        Route::get('analytics/workflows/{workflow}/metrics', [AnalyticsController::class, 'workflowMetrics']);
        Route::get('analytics/users/{user}/metrics', [AnalyticsController::class, 'userMetrics']);
        Route::get('analytics/bottlenecks', [AnalyticsController::class, 'bottlenecks']);
        Route::get('analytics/trends', [AnalyticsController::class, 'trends']);
        Route::get('analytics/top-performers', [AnalyticsController::class, 'topPerformers']);
        Route::post('analytics/compare-workflows', [AnalyticsController::class, 'compareWorkflows']);

        // Reports routes
        Route::apiResource('reports', ReportController::class);
        Route::post('reports/{report}/execute', [ReportController::class, 'execute']);
        Route::get('reports/{report}/executions', [ReportController::class, 'executions']);
        Route::get('reports/{report}/statistics', [ReportController::class, 'statistics']);
        Route::get('reports-audit', [ReportController::class, 'auditReport']);
        Route::get('reports-types', [ReportController::class, 'reportTypes']);
        Route::get('reports-frequencies', [ReportController::class, 'frequencies']);

        // Weightage routes
        Route::get('steps/{step}/weightage/breakdown', [WeightageController::class, 'getStepBreakdown']);
        Route::get('steps/{step}/weightage/remaining', [WeightageController::class, 'getRemainingApprovals']);
        Route::get('steps/{step}/weightage/percentages', [WeightageController::class, 'getApproverPercentages']);
        Route::put('steps/{step}/weightage/minimum-percentage', [WeightageController::class, 'updateMinimumPercentage']);
        Route::put('steps/{step}/weightage/bulk-update', [WeightageController::class, 'bulkUpdateWeightages']);
        Route::post('steps/{step}/weightage/validate', [WeightageController::class, 'validateDistribution']);
        Route::get('requests/{request}/weightage/breakdown', [WeightageController::class, 'getRequestBreakdown']);
        Route::put('approvers/{approver}/weightage', [WeightageController::class, 'updateApproverWeightage']);
        Route::post('weightage/suggest', [WeightageController::class, 'suggestDistribution']);
    });
