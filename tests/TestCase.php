<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Tests\Support\TestingDatabaseGuard;

abstract class TestCase extends BaseTestCase
{
    // ponytail: Drop PostgreSQL custom types (enums, etc.) during migrate:fresh.
    // Without this, 42 enum columns leave orphaned pg_type entries that collide
    // on re-migration (pg_type_typname_nsp_index already exists).
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
