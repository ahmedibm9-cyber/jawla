<?php

/**
 * Test bootstrap — runs once before the test suite.
 *
 * Migrates the test database so tests using DatabaseTransactions
 * (instead of RefreshDatabase) have tables available.
 */

use Illuminate\Contracts\Console\Kernel;

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

// Only migrate if not already migrated (e.g. a previous test class did it)
if (! $app->make('db')->connection()->getSchemaBuilder()->hasTable('migrations')) {
    $app->make(Kernel::class)->call('migrate:fresh', [
        '--env' => 'testing',
        '--quiet' => true,
    ]);
}
