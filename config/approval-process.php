<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Approval Process Configuration
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | API Configuration
    |--------------------------------------------------------------------------
    | Configure API authentication middleware
    | 
    | Options:
    |   - ['api', 'auth:sanctum'] - For Sanctum (SPA/mobile apps)
    |   - ['api', 'auth:api'] - For Passport (OAuth2)
    |   - ['web', 'auth'] - For web session auth (monolithic apps)
    */
    'api' => [
        // API middleware - Examples: 'web,auth' | 'api,auth:sanctum' | 'api,auth:api' (JWT)
        'middleware' => array_map('trim', explode(',', env('APPROVAL_API_MIDDLEWARE', 'web,auth'))),
        
        // Auth guard - Examples: 'web' | 'api' | 'sanctum' | 'jwt'
        'guard' => env('APPROVAL_API_GUARD', 'web'),
    ],

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
        'layout' => env('APPROVAL_UI_LAYOUT', 'approval-process::layout'), // Custom layout: 'layouts.app' or default package layout
        'items_per_page' => 15,
        'enable_dark_mode' => true,
        'timezone' => env('APP_TIMEZONE', 'UTC'),
        
        /*
        |----------------------------------------------------------------------
        | Admin Panel Middleware
        |----------------------------------------------------------------------
        | Middleware for the standalone admin panel at /approval-admin
        | 
        | For monolithic apps (Blade/Inertia):
        |   - ['web', 'auth']
        | 
        | For API-first apps (Sanctum/JWT):
        |   - ['web'] - No auth middleware, handle in frontend
        |   - Or use custom middleware to check token
        | 
        | Examples:
        |   - ['web', 'auth', 'can:manage-approvals'] - With permission
        |   - [] - No middleware (public access)
        */
        'admin_panel_middleware' => explode(',', env('APPROVAL_ADMIN_MIDDLEWARE', 'web,auth')),
        
        /*
        |----------------------------------------------------------------------
        | Widget Auto-Refresh Configuration
        |----------------------------------------------------------------------
        | Configure auto-refresh intervals for widgets (in milliseconds)
        | Set to 0 to disable auto-refresh
        */
        'widget_refresh' => [
            'stats' => env('APPROVAL_WIDGET_REFRESH_STATS', 30000),           // 30 seconds
            'pending_approvals' => env('APPROVAL_WIDGET_REFRESH_PENDING', 10000), // 10 seconds
            'activity_feed' => env('APPROVAL_WIDGET_REFRESH_ACTIVITY', 30000),    // 30 seconds
            'workflows' => env('APPROVAL_WIDGET_REFRESH_WORKFLOWS', 0),           // Disabled by default
        ],
        
        /*
        |----------------------------------------------------------------------
        | Custom Authentication
        |----------------------------------------------------------------------
        | For advanced auth scenarios, you can inject custom JavaScript
        | to configure the auth provider. Leave null for default behavior.
        |
        | Example:
        | 'custom_auth_script' => 'window.ApprovalAuth = { getHeaders: () => ({}) };'
        */
        'custom_auth_script' => null,
        
        /*
        |----------------------------------------------------------------------
        | Login URL
        |----------------------------------------------------------------------
        | Where to redirect on 401 Unauthorized
        */
        'login_url' => env('APPROVAL_LOGIN_URL', '/login'),
        
        /*
        |----------------------------------------------------------------------
        | Dynamic Entities & Roles
        |----------------------------------------------------------------------
        | Entities and roles are now managed dynamically through the database.
        | 
        | - Entities: Stored in `approval_approvable_entities` table
        | - Roles: Fetched from Spatie Permission or users table
        | 
        | API Endpoints:
        |   GET /api/approval-process/entities - List all entities
        |   GET /api/approval-process/roles - List all roles
        |   GET /api/approval-process/entities/discover - Discover models/tables
        |
        | No configuration needed here!
        */
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
