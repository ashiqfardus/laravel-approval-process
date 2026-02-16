<?php

namespace AshiqFardus\ApprovalProcess\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use AshiqFardus\ApprovalProcess\ApprovalProcessServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

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
        $app['config']->set('approval-process.paths.api_prefix', 'api/approval');
    }

    /**
     * Create a test user.
     */
    protected function createUser(array $attributes = [])
    {
        return \Illuminate\Foundation\Auth\User::create(array_merge([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ], $attributes));
    }
}
