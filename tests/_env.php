<?php

declare(strict_types=1);
use Tests\Support\TestingDatabaseGuard;

/**
 * Test environment pinning — loaded by tests/bootstrap.php BEFORE Laravel
 * boots, so Laravel's Dotenv loader (createImmutable) sees these values
 * are already set in the environment and does not overwrite them with
 * whatever the project's .env file happens to contain.
 *
 * Why this file exists in addition to phpunit.xml's <env> block:
 *   phpunit.xml only sets values when running under phpunit. Any code path
 *   that loads bootstrap/app.php directly (custom workers, ad-hoc artisan
 *   invocations from the test tree) bypasses phpunit.xml. The test suite
 *   also has to defend against a stale project .env whose APP_ENV=local
 *   and DB_DATABASE=jawla — without this, the suite silently runs against
 *   the development database and the ActiveCompanyContext tenancy guard
 *   trips on the first service-level unit test.
 *
 * Keep this array in sync with phpunit.xml's <env> block. Set
 * JAWLA_TEST_DATABASE to a jawla_test_* name when concurrent local runners
 * need separate databases. The shared TestingDatabaseGuard enforces the
 * dangerous subset at every boundary.
 */
$database = getenv('JAWLA_TEST_DATABASE');
$database = is_string($database) && $database !== '' ? $database : 'jawla_test';

TestingDatabaseGuard::assertSafe('testing', 'pgsql', $database);

$pinned = [
    'APP_ENV' => 'testing',
    'JAWLA_MODE' => 'demo',
    'APP_MAINTENANCE_DRIVER' => 'file',
    'BCRYPT_ROUNDS' => '4',
    'BROADCAST_CONNECTION' => 'null',
    'CACHE_STORE' => 'array',
    'DB_CONNECTION' => 'pgsql',
    'DB_HOST' => '127.0.0.1',
    'DB_PORT' => '5432',
    'DB_DATABASE' => $database,
    'DB_USERNAME' => 'postgres',
    'DB_PASSWORD' => 'postgres',
    'MAIL_MAILER' => 'array',
    'QUEUE_CONNECTION' => 'sync',
    'SESSION_DRIVER' => 'array',
    'PULSE_ENABLED' => 'false',
    'TELESCOPE_ENABLED' => 'false',
    'NIGHTWATCH_ENABLED' => 'false',
];

foreach ($pinned as $key => $value) {
    putenv("{$key}={$value}");
    $_ENV[$key] = $value;
    $_SERVER[$key] = $value;
}

unset($database, $key, $value, $pinned);
