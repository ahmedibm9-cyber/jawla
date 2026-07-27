<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Tests\Support\TestingDatabaseGuard;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        TestingDatabaseGuard::assertSafe(
            (string) getenv('APP_ENV'),
            (string) getenv('DB_CONNECTION'),
            (string) getenv('DB_DATABASE'),
        );

        parent::setUp();

        // The base TestCase re-reads .env inside createApplication(), which
        // would clobber the phpunit.xml-provided test environment with
        // whatever the project .env has (typically APP_ENV=local and
        // DB_DATABASE=jawla). Re-assert the testing environment on the
        // freshly booted app so app()->environment(),
        // app()->runningUnitTests(), and the DB connection all report the
        // truth. Without this, service-level unit tests trip the
        // ActiveCompanyContext tenancy guard, every config that branches on
        // environment misbehaves, and the suite accidentally runs against
        // the development database.
        $this->app->detectEnvironment(fn (): string => 'testing');

        $testDatabase = (string) getenv('DB_DATABASE');
        if ($testDatabase !== '') {
            config([
                'database.connections.pgsql.database' => $testDatabase,
                'database.default' => 'pgsql',
            ]);
            $this->app->make('db')->purge();
        }
    }
}
