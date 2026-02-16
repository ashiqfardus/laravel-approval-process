<?php

use Illuminate\Support\Facades\Route;
use AshiqFardus\ApprovalProcess\Http\Controllers\Web\AdminController;
use AshiqFardus\ApprovalProcess\Http\Controllers\Web\WorkflowDesignerController;
use AshiqFardus\ApprovalProcess\Http\Controllers\Web\DashboardWebController;
use AshiqFardus\ApprovalProcess\Http\Controllers\Web\RequestWebController;

Route::prefix(config('approval-process.paths.web_prefix'))
    ->middleware(['web', 'auth'])
    ->name('approval-process.')
    ->group(function () {
        // Dashboard
        Route::get('/', [DashboardWebController::class, 'index'])->name('dashboard');
        Route::get('/dashboard', [DashboardWebController::class, 'index'])->name('dashboard.index');
        
        // Workflows
        Route::get('/workflows', [AdminController::class, 'workflows'])->name('workflows.index');
        Route::get('/workflows/create', [AdminController::class, 'createWorkflow'])->name('workflows.create');
        Route::get('/workflows/{workflow}/edit', [AdminController::class, 'editWorkflow'])->name('workflows.edit');
        Route::get('/workflows/{workflow}/view', [AdminController::class, 'viewWorkflow'])->name('workflows.view');
        
        // Workflow Designer
        Route::get('/workflows/{workflow}/designer', [WorkflowDesignerController::class, 'show'])->name('workflows.designer');
        Route::post('/workflows/{workflow}/designer/save', [WorkflowDesignerController::class, 'save'])->name('workflows.designer.save');
        Route::get('/workflows/{workflow}/designer/export', [WorkflowDesignerController::class, 'export'])->name('workflows.designer.export');
        Route::post('/workflows/designer/import', [WorkflowDesignerController::class, 'import'])->name('workflows.designer.import');
        
        // Approval Requests
        Route::get('/requests', [RequestWebController::class, 'index'])->name('requests.index');
        Route::get('/requests/create', [RequestWebController::class, 'create'])->name('requests.create');
        Route::get('/requests/{request}', [RequestWebController::class, 'show'])->name('requests.show');
        Route::get('/requests/{request}/timeline', [RequestWebController::class, 'timeline'])->name('requests.timeline');
        
        // My Approvals
        Route::get('/my-approvals', [RequestWebController::class, 'myApprovals'])->name('my-approvals');
        Route::get('/my-requests', [RequestWebController::class, 'myRequests'])->name('my-requests');
        
        // Reports
        Route::get('/reports', [AdminController::class, 'reports'])->name('reports.index');
        Route::get('/reports/{report}/view', [AdminController::class, 'viewReport'])->name('reports.view');
        
        // Analytics
        Route::get('/analytics', [AdminController::class, 'analytics'])->name('analytics.index');
        Route::get('/analytics/workflows', [AdminController::class, 'workflowAnalytics'])->name('analytics.workflows');
        Route::get('/analytics/users', [AdminController::class, 'userAnalytics'])->name('analytics.users');
        
        // Settings
        Route::get('/settings', [AdminController::class, 'settings'])->name('settings');
    });
