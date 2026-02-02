# Installation Guide

## System Requirements

- PHP 8.1 or higher
- Laravel 10, 11, or 12
- MySQL 5.7+ or PostgreSQL 10+
- Composer

## Step-by-Step Installation

### 1. Install via Composer

```bash
composer require approval-workflow/laravel-approval-process
```

### 2. Publish Configuration

```bash
php artisan vendor:publish --provider="ApprovalWorkflow\ApprovalProcess\ApprovalProcessServiceProvider" --tag="approval-process-config"
```

This creates `config/approval-process.php` where you can customize settings.

### 3. Run Migrations

```bash
php artisan migrate
```

This creates the following tables:
- `approval_workflows`
- `approval_steps`
- `approval_requests`
- `approval_actions`
- `approval_approvers`
- `approval_delegations`

### 4. Publish Views (Optional)

To customize the frontend views:

```bash
php artisan vendor:publish --provider="ApprovalWorkflow\ApprovalProcess\ApprovalProcessServiceProvider" --tag="approval-process-views"
```

Views will be published to `resources/views/vendor/approval-process/`

### 5. Publish Assets (Optional)

To customize CSS/JS:

```bash
php artisan vendor:publish --provider="ApprovalWorkflow\ApprovalProcess\ApprovalProcessServiceProvider" --tag="approval-process-assets"
```

Assets will be published to `public/vendor/approval-process/`

## Configuration Options

Edit `config/approval-process.php`:

```php
return [
    'database' => [
        'prefix' => 'approval_', // Table prefix
        'connection' => env('DB_CONNECTION', 'mysql'),
    ],

    'models' => [
        // Use custom models if needed
        'workflow' => \ApprovalWorkflow\ApprovalProcess\Models\Workflow::class,
        'approval_request' => \ApprovalWorkflow\ApprovalProcess\Models\ApprovalRequest::class,
        // ... other models
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
        'working_days' => [1, 2, 3, 4, 5], // Mon-Fri
        'auto_escalate' => true,
        'auto_escalate_percentage' => 80,
    ],

    'security' => [
        'require_approval_reason_on_reject' => true,
        'require_approval_reason_on_override' => true,
        'log_ip_address' => true,
        'log_device_info' => true,
        'enable_signature_verification' => true,
    ],

    'ui' => [
        'framework' => env('APPROVAL_UI_FRAMEWORK', 'tailwind'),
        'items_per_page' => 15,
        'enable_dark_mode' => true,
        'timezone' => env('APP_TIMEZONE', 'UTC'),
    ],

    'cache' => [
        'enable' => true,
        'ttl' => 3600,
    ],

    'paths' => [
        'views' => 'vendor.approval-process.approvals',
        'api_prefix' => 'api/approval-process',
        'web_prefix' => 'approval-process',
    ],
];
```

## Using with Different Laravel Versions

### Laravel 10
```bash
composer require approval-workflow/laravel-approval-process "^1.0"
```

### Laravel 11
```bash
composer require approval-workflow/laravel-approval-process "^2.0"
```

### Laravel 12
```bash
composer require approval-workflow/laravel-approval-process "^3.0"
```

## Database Connection

By default, the package uses the default database connection. To use a different connection, update `.env`:

```env
APPROVAL_DB_CONNECTION=secondary
```

Then update `config/approval-process.php`:

```php
'database' => [
    'connection' => env('APPROVAL_DB_CONNECTION', 'mysql'),
],
```

## Authentication Setup

The package assumes you have a User model. Update `config/auth.php` if using a different model:

```php
'providers' => [
    'users' => [
        'driver' => 'eloquent',
        'model' => App\Models\YourUserModel::class,
    ],
],
```

## Environment Variables

Add these to your `.env` file:

```env
# Approval Process Configuration
APPROVAL_UI_FRAMEWORK=tailwind
APPROVAL_HOLIDAYS="2024-12-25,2024-12-26,2025-01-01"

# Notifications
APPROVAL_NOTIFICATIONS_QUEUE=true
QUEUE_CONNECTION=redis

# Enable Real-time Updates
APPROVAL_ENABLE_REALTIME=false
```

## Verification

Run these commands to verify installation:

```bash
# Check migrations are installed
php artisan migrate:status

# Test the API
php artisan tinker

# Create a workflow
$workflow = \ApprovalWorkflow\ApprovalProcess\Models\Workflow::create([
    'name' => 'Test Workflow',
    'model_type' => 'App\Models\YourModel',
]);

exit
```

## Next Steps

1. [Read the Quick Start Guide](README.md#quick-start)
2. [Create your first workflow](README.md#quick-start)
3. [Customize views and styles](README.md#frontend-views)
4. [Set up notifications](README.md#notifications)
5. [Integrate with your models](README.md#1-make-your-model-approvable)

## Troubleshooting

### Migrations not running
```bash
php artisan migrate:refresh --seed
```

### Views not publishing
```bash
php artisan vendor:publish --force --tag="approval-process-views"
```

### Clear cache
```bash
php artisan cache:clear
php artisan config:clear
```

## Need Help?

- Check the [README.md](README.md)
- Visit: https://github.com/approval-workflow/laravel-approval-process
- Report issues: https://github.com/approval-workflow/laravel-approval-process/issues
