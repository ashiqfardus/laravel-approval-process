<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Approval Process Configuration
    |--------------------------------------------------------------------------
    */

    'database' => [
        'prefix' => 'approval_',
        'connection' => env('DB_CONNECTION', 'mysql'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Approvable Models (For Admin Panel)
    |--------------------------------------------------------------------------
    | List all models that can have approval workflows
    | Users will select from this list when creating workflows
    */
    'approvable_models' => [
        'App\Models\Offer' => 'Offer',
        'App\Models\PurchaseOrder' => 'Purchase Order',
        'App\Models\ExpenseClaim' => 'Expense Claim',
        'App\Models\LeaveRequest' => 'Leave Request',
        'App\Models\Invoice' => 'Invoice',
        // Add more models here as needed
    ],

    /*
    |--------------------------------------------------------------------------
    | Available Roles (For Admin Panel)
    |--------------------------------------------------------------------------
    | Roles that can be assigned as approvers
    | Update this list based on your application's roles
    */
    'available_roles' => [
        'manager' => 'Manager',
        'finance' => 'Finance',
        'director' => 'Director',
        'hr' => 'HR',
        'ceo' => 'CEO',
        'team_lead' => 'Team Lead',
        // Add more roles here
    ],

    'models' => [
        'workflow' => \AshiqFardus\ApprovalProcess\Models\Workflow::class,
        'approval_step' => \AshiqFardus\ApprovalProcess\Models\ApprovalStep::class,
        'approval_request' => \AshiqFardus\ApprovalProcess\Models\ApprovalRequest::class,
        'approval_action' => \AshiqFardus\ApprovalProcess\Models\ApprovalAction::class,
        'approver' => \AshiqFardus\ApprovalProcess\Models\Approver::class,
        'delegation' => \AshiqFardus\ApprovalProcess\Models\Delegation::class,
    ],

    'features' => [
        'enable_signatures' => true,
        'enable_attachments' => true,
        'enable_email_actions' => true,
        'enable_webhooks' => true,
        'enable_audit_log' => true,
        'enable_real_time_updates' => false,
    ],

    'notifications' => [
        'channels' => ['mail', 'database'],
        'queue' => true,
        'queue_connection' => env('QUEUE_CONNECTION', 'sync'),
    ],

    'sla' => [
        'enable_working_days' => true,
        'working_days' => [1, 2, 3, 4, 5], // Monday to Friday
        'holidays' => env('APPROVAL_HOLIDAYS', ''),
        'auto_escalate' => true,
        'auto_escalate_percentage' => 80, // Escalate at 80% of SLA time
    ],

    'security' => [
        'require_approval_reason_on_reject' => true,
        'require_approval_reason_on_override' => true,
        'log_ip_address' => true,
        'log_device_info' => true,
        'enable_signature_verification' => true,
    ],

    'ui' => [
        'framework' => env('APPROVAL_UI_FRAMEWORK', 'tailwind'), // tailwind, bootstrap
        'items_per_page' => 15,
        'enable_dark_mode' => true,
        'timezone' => env('APP_TIMEZONE', 'UTC'),
    ],

    'cache' => [
        'enable' => true,
        'ttl' => 3600, // 1 hour
    ],

    'paths' => [
        'views' => 'vendor.approval-process.approvals',
        'api_prefix' => 'api/approval-process',
        'web_prefix' => 'approval-process',
    ],
];
