<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use Tests\Support\TestingDatabaseGuard;

abstract class TestCase extends BaseTestCase
{
    protected bool $dropTypes = true;

    protected function setUp(): void
    {
        TestingDatabaseGuard::assertSafe(
            (string) getenv('APP_ENV'),
            (string) getenv('DB_CONNECTION'),
            (string) getenv('DB_DATABASE'),
        );

        parent::setUp();

        $this->app->detectEnvironment(fn (): string => 'testing');
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Reset the database connection to prevent cascading SQLSTATE[25P02]
        // errors from aborted transactions in previous tests.
        // DB::purge() removes the default connection from the manager,
        // forcing a fresh connection on next use.
        try {
            DB::purge();
        } catch (\Throwable) {
            // Ignore — connection may already be broken
        }

        gc_collect_cycles();
    }
}
