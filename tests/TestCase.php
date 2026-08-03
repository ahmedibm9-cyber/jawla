<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
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

        gc_collect_cycles();
    }
}
