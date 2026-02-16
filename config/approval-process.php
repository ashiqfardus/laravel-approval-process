<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Approval Process Configuration
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | UI Configuration
    |--------------------------------------------------------------------------
    | Enable/disable the built-in Blade UI
    | Set to false if using Vue/React/Next.js or other SPA frontend
    */
    'ui' => [
        'enabled' => env('APPROVAL_UI_ENABLED', true),
        'framework' => env('APPROVAL_UI_FRAMEWORK', 'tailwind'), // tailwind, bootstrap
        'items_per_page' => 15,
        'enable_dark_mode' => true,
        'timezone' => env('APP_TIMEZONE', 'UTC'),
        
        /*
        |----------------------------------------------------------------------
        | Approvable Models (For Blade UI Dropdowns)
        |----------------------------------------------------------------------
        | Only used if UI is enabled
        | List all models that can have approval workflows
        | For SPA frontends, fetch this from your own API
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
        |----------------------------------------------------------------------
        | Available Roles (For Blade UI Dropdowns)
        |----------------------------------------------------------------------
        | Only used if UI is enabled
        | Roles that can be assigned as approvers
        | For SPA frontends, fetch this from your own API
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
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Configuration
    |--------------------------------------------------------------------------
    */
    'database' => [
        'prefix' => 'approval_',
        'connection' => env('DB_CONNECTION', 'mysql'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Model Mappings
    |--------------------------------------------------------------------------
    | Core model class mappings (always required)
    */
    'models' => [
        'workflow' => \AshiqFardus\ApprovalProcess\Models\Workflow::class,
        'approval_step' => \AshiqFardus\ApprovalProcess\Models\ApprovalStep::class,
        'approval_request' => \AshiqFardus\ApprovalProcess\Models\ApprovalRequest::class,
        'approval_action' => \AshiqFardus\ApprovalProcess\Models\ApprovalAction::class,
        'approver' => \AshiqFardus\ApprovalProcess\Models\Approver::class,
        'delegation' => \AshiqFardus\ApprovalProcess\Models\Delegation::class,
        'user' => \Illuminate\Foundation\Auth\User::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Feature Flags
    |--------------------------------------------------------------------------
    */
    'features' => [
        'enable_signatures' => true,
        'enable_attachments' => true,
        'enable_email_actions' => true,
        'enable_webhooks' => true,
        'enable_audit_log' => true,
        'enable_real_time_updates' => env('APPROVAL_REALTIME_ENABLED', false),
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

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    */
    'cache' => [
        'enable' => true,
        'ttl' => 3600, // 1 hour
    ],

    /*
    |--------------------------------------------------------------------------
    | Path Configuration
    |--------------------------------------------------------------------------
    */
    'paths' => [
        'views' => 'vendor.approval-process.approvals',
        'api_prefix' => env('APPROVAL_API_PREFIX', 'api/approval-process'),
        'web_prefix' => env('APPROVAL_WEB_PREFIX', 'approval-process'),
    ],

    'storage' => [
        'disk' => env('APPROVAL_STORAGE_DISK', 'local'),
        'attachments_path' => 'approval-attachments',
        'generated_documents_path' => 'generated-documents',
        'reports_path' => 'reports',
        'max_file_size' => 10 * 1024 * 1024, // 10MB
        'allowed_mime_types' => [
            'application/pdf',
            'image/jpeg',
            'image/png',
            'image/gif',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ],
    ],

    'analytics' => [
        'enable_automatic_calculation' => env('APPROVAL_AUTO_ANALYTICS', true),
        'calculation_schedule' => 'daily', // daily, weekly, monthly
        'bottleneck_threshold' => 10, // Minimum pending requests to flag as bottleneck
        'retention_days' => 365, // Days to keep analytics data
    ],
];
