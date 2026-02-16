<?php

namespace AshiqFardus\ApprovalProcess\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use AshiqFardus\ApprovalProcess\ApprovalProcessServiceProvider;
use Illuminate\Foundation\Auth\User;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        // Create users table for testing
        $this->app['db']->connection()->getSchemaBuilder()->create('users', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->timestamps();
        });

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }

    protected function getPackageProviders($app)
    {
        return [
            ApprovalProcessServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        // Setup approval process config
        $app['config']->set('approval-process.paths.api_prefix', 'api/approval-process');
        
        // Setup auth guards for testing - use a custom guard that always passes
        $app['config']->set('auth.guards.api', [
            'driver' => 'session',
            'provider' => 'users',
        ]);
        
        $app['config']->set('auth.providers.users', [
            'driver' => 'eloquent',
            'model' => \Illuminate\Foundation\Auth\User::class,
        ]);
        
        // Register a custom auth guard for testing that always authenticates
        $app['auth']->extend('test', function ($app, $name, $config) {
            return new class($app['auth']->createUserProvider($config['provider'] ?? null)) extends \Illuminate\Auth\SessionGuard {
                public function check() { return true; }
                public function guest() { return false; }
                public function user() { return \App\Models\User::first() ?? new \Illuminate\Foundation\Auth\User(); }
                public function id() { return $this->user()?->id ?? 1; }
            };
        });
    }

    /**
     * Create a test user.
     */
    protected function createUser(array $attributes = [])
    {
        static $userCounter = 0;
        $userCounter++;
        
        $user = new class extends User {
            protected $table = 'users';
            protected $guarded = [];
        };

        return $user::create(array_merge([
            'name' => 'Test User ' . $userCounter,
            'email' => 'test' . $userCounter . '@example.com',
            'password' => bcrypt('password'),
        ], $attributes));
    }
}
