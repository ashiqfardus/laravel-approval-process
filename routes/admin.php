<?php

use Illuminate\Support\Facades\Route;
use AshiqFardus\ApprovalProcess\Http\Controllers\Admin\WorkflowAdminController;

/*
|--------------------------------------------------------------------------
| Admin Panel Routes (For Managing Workflows)
|--------------------------------------------------------------------------
| These routes allow administrators to manage approval workflows
| without writing code. Requires 'admin' role/permission.
*/

Route::prefix('admin/approval-workflows')
    ->name('approval-admin.workflows.')
    ->middleware(['web', 'auth', 'can:manage-workflows'])
    ->group(function () {

        // Workflow Management
        Route::get('/', [WorkflowAdminController::class, 'index'])->name('index');
        Route::get('/create', [WorkflowAdminController::class, 'create'])->name('create');
        Route::post('/', [WorkflowAdminController::class, 'store'])->name('store');
        Route::get('/{workflow}', [WorkflowAdminController::class, 'edit'])->name('edit');
        Route::put('/{workflow}', [WorkflowAdminController::class, 'update'])->name('update');
        Route::delete('/{workflow}', [WorkflowAdminController::class, 'destroy'])->name('destroy');

        // Workflow Actions
        Route::post('/{workflow}/toggle-status', [WorkflowAdminController::class, 'toggleStatus'])->name('toggle-status');
        Route::post('/{workflow}/clone', [WorkflowAdminController::class, 'clone'])->name('clone');

        // Approval Level/Step Management
        Route::post('/{workflow}/steps', [WorkflowAdminController::class, 'addStep'])->name('steps.add');
        Route::put('/steps/{step}', [WorkflowAdminController::class, 'updateStep'])->name('steps.update');
        Route::delete('/steps/{step}', [WorkflowAdminController::class, 'deleteStep'])->name('steps.delete');
        Route::post('/{workflow}/steps/reorder', [WorkflowAdminController::class, 'reorderSteps'])->name('steps.reorder');

        // Approver Management
        Route::post('/steps/{step}/approvers', [WorkflowAdminController::class, 'addApprover'])->name('approvers.add');
        Route::delete('/approvers/{approver}', [WorkflowAdminController::class, 'removeApprover'])->name('approvers.remove');
    });
