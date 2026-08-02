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

        $this->app->detectEnvironment(fn (): string => 'testing');
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // After parent::tearDown() destroys the app container, force-close
        // any lingering PostgreSQL connections and let PHP garbage-collect
        // the PDO handles. Without this, the previous test's connection may
        // still hold row locks when the NEXT test's RefreshDatabase runs
        // migrate:fresh (DROP TABLE CASCADE), causing intermittent deadlocks.
        gc_collect_cycles();
    }
}
