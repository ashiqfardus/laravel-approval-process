<?php

namespace AshiqFardus\ApprovalProcess;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Migrations\Migrator;
use AshiqFardus\ApprovalProcess\Commands\PublishAssetsCommand;
use AshiqFardus\ApprovalProcess\Commands\MakeMigrationCommand;

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

        // Load routes
        $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');

        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                PublishAssetsCommand::class,
                MakeMigrationCommand::class,
            ]);
        }
    }
}
