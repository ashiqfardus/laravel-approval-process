<?php

namespace AshiqFardus\ApprovalProcess;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Migrations\Migrator;
use AshiqFardus\ApprovalProcess\Commands\PublishAssetsCommand;
use AshiqFardus\ApprovalProcess\Commands\MakeMigrationCommand;
use AshiqFardus\ApprovalProcess\Commands\CreateWorkflowCommand;
use AshiqFardus\ApprovalProcess\Commands\ListWorkflowsCommand;
use AshiqFardus\ApprovalProcess\Commands\CheckEscalationsCommand;
use AshiqFardus\ApprovalProcess\Commands\SendRemindersCommand;
use AshiqFardus\ApprovalProcess\Commands\EndDelegationsCommand;
use AshiqFardus\ApprovalProcess\Services\ApprovalEngine;
use AshiqFardus\ApprovalProcess\Services\ApproverResolver;

class ApprovalProcessServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/approval-process.php',
            'approval-process'
        );

        // Register service bindings
        $this->app->singleton('approval-engine', fn () => new ApprovalEngine());
        $this->app->singleton('approval-resolver', fn () => new ApproverResolver());
    }

    public function boot(): void
    {
        // Publish configuration
        $this->publishes([
            __DIR__ . '/../config/approval-process.php' => config_path('approval-process.php'),
        ], 'approval-process-config');

        // Publish migrations
        $this->publishes([
            __DIR__ . '/../database/migrations' => database_path('migrations'),
        ], 'approval-process-migrations');

        // Publish views
        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/approval-process'),
        ], 'approval-process-views');

        // Publish assets
        $this->publishes([
            __DIR__ . '/../resources/assets' => public_path('vendor/approval-process'),
        ], 'approval-process-assets');

        // Load API routes (always)
        $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');

        // Load UI routes and views (only if enabled)
        if (config('approval-process.ui.enabled', true)) {
            $this->loadViewsFrom(__DIR__ . '/../resources/views', 'approval-process');
            $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        }

        // Load broadcast channels (only if real-time is enabled)
        if (config('approval-process.features.enable_real_time_updates', false)) {
            $this->loadRoutesFrom(__DIR__ . '/../routes/channels.php');
        }

        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                PublishAssetsCommand::class,
                MakeMigrationCommand::class,
                CreateWorkflowCommand::class,
                ListWorkflowsCommand::class,
                CheckEscalationsCommand::class,
                SendRemindersCommand::class,
                EndDelegationsCommand::class,
            ]);
        }
    }
}
