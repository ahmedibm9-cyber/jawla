<?php

namespace Tests\Support;

use LogicException;

final class TestingDatabaseGuard
{
    public static function assertSafe(string $environment, string $connection, string $database): void
    {
        if ($environment !== 'testing') {
            throw new LogicException('APP_ENV must be testing before the test suite can access a database.');
        }

        if ($connection !== 'pgsql') {
            throw new LogicException('The Jawla test suite requires an isolated PostgreSQL connection.');
        }

        if ($database !== 'jawla_test' && ! str_starts_with($database, 'jawla_test_')) {
            throw new LogicException('The test database must use the dedicated jawla_test namespace.');
        }
    }
}
