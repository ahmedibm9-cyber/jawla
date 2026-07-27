<?php

declare(strict_types=1);

/**
 * Test bootstrap — runs once before the test suite.
 *
 * The suite is allowed to create and migrate databases only inside the
 * dedicated jawla_test namespace. This makes a fresh sequential run and
 * Laravel's per-worker parallel databases self-provisioning without ever
 * falling back to the development database.
 */

use Illuminate\Contracts\Console\Kernel;
use Tests\Support\TestingDatabaseGuard;

require_once __DIR__.'/../vendor/autoload.php';

$environment = (string) getenv('APP_ENV');
$connection = (string) getenv('DB_CONNECTION');
$database = (string) getenv('DB_DATABASE');

TestingDatabaseGuard::assertSafe($environment, $connection, $database);

$host = (string) getenv('DB_HOST');
$port = (string) getenv('DB_PORT');
$username = (string) getenv('DB_USERNAME');
$password = (string) getenv('DB_PASSWORD');

$admin = new PDO(
    "pgsql:host={$host};port={$port};dbname=postgres",
    $username,
    $password,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
);

$exists = $admin->prepare('SELECT 1 FROM pg_database WHERE datname = :database');
$exists->execute(['database' => $database]);

if (! $exists->fetchColumn()) {
    $quotedDatabase = '"'.str_replace('"', '""', $database).'"';

    try {
        $admin->exec("CREATE DATABASE {$quotedDatabase}");
    } catch (PDOException $exception) {
        // Parallel workers may race to create the safe base database once.
        if ($exception->getCode() !== '42P04') {
            throw $exception;
        }
    }
}

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

// Force APP_ENV=testing on the bootstrapped app instance. Without this,
// .env's APP_ENV=local wins (Laravel uses mutable Dotenv) and the unit
// suite misbehaves as a non-test environment: app()->runningUnitTests()
// returns false, the ActiveCompanyContext's "unscoped" flag stays off,
// and every service-level unit test trips the tenancy guard.
$app['env'] = 'testing';

// DatabaseTransactions tests can run before the first RefreshDatabase test.
if (! $app->make('db')->connection()->getSchemaBuilder()->hasTable('migrations')) {
    $app->make(Kernel::class)->call('migrate', [
        '--env' => 'testing',
        '--force' => true,
        '--quiet' => true,
    ]);
}

$app->make('db')->purge();
$app->flush();
unset($app);

// The bootstrap application installs Laravel's handlers. TestCase creates its
// own application per test, so restore PHPUnit's handlers before discovery.
restore_error_handler();
restore_exception_handler();
