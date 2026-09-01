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

        // Reset database connection state to prevent cascading SQLSTATE[25P02]
        // errors from aborted transactions in previous tests.
        DB::purge();
        DB::reconnect();

        gc_collect_cycles();
    }
}
