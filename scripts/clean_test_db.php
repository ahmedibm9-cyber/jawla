<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;

// Clean jawla_test DB and run migrations in testing mode
$pdo = new PDO('pgsql:host=127.0.0.1;port=5432;dbname=jawla_test', 'postgres', 'postgres');

// Terminate all other connections
$stale = $pdo->query("SELECT pid FROM pg_stat_activity WHERE datname = 'jawla_test' AND pid <> pg_backend_pid()")->fetchAll();
foreach ($stale as $row) {
    $pdo->exec("SELECT pg_terminate_backend({$row['pid']})");
}
usleep(500000);
$pdo->exec('DROP SCHEMA public CASCADE; CREATE SCHEMA public;');
echo "Schema cleaned\n";

// Now run artisan migrate in testing mode
putenv('APP_ENV=testing');
putenv('DB_CONNECTION=pgsql');
putenv('DB_DATABASE=jawla_test');

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

Artisan::call('migrate', ['--force' => true]);
echo Artisan::output();
echo "Done\n";
