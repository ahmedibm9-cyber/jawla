<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;

// Clean jawla_test DB and run migrations in testing mode

// Step 1: Forcefully kill all other connections
for ($killRound = 1; $killRound <= 3; $killRound++) {
    $pdo = new PDO('pgsql:host=127.0.0.1;port=5432;dbname=jawla_test', 'postgres', 'postgres');
    $stale = $pdo->query("SELECT pid FROM pg_stat_activity WHERE datname = 'jawla_test' AND pid <> pg_backend_pid()")->fetchAll();
    if ($stale === []) {
        unset($pdo);
        break;
    }
    foreach ($stale as $row) {
        $pdo->exec("SELECT pg_terminate_backend({$row['pid']})");
    }
    unset($pdo);
    usleep(2000000); // 2s between kill rounds
}

// Step 2: Wait for connections to actually close
for ($wait = 1; $wait <= 10; $wait++) {
    $check = new PDO('pgsql:host=127.0.0.1;port=5432;dbname=jawla_test', 'postgres', 'postgres');
    $remaining = $check->query("SELECT count(*) FROM pg_stat_activity WHERE datname = 'jawla_test' AND pid <> pg_backend_pid()")->fetchColumn();
    unset($check);
    if ((int) $remaining === 0) {
        break;
    }
    echo "Waiting for $remaining connection(s) to close... (wait $wait)\n";
    usleep(1000000);
}

// Step 3: Drop and recreate schema with a fresh connection
for ($attempt = 1; $attempt <= 5; $attempt++) {
    try {
        $dropper = new PDO('pgsql:host=127.0.0.1;port=5432;dbname=jawla_test', 'postgres', 'postgres');
        $dropper->exec('DROP SCHEMA public CASCADE; CREATE SCHEMA public;');
        unset($dropper);
        echo "Schema cleaned (attempt $attempt)\n";
        break;
    } catch (PDOException $e) {
        unset($dropper);
        if ($attempt === 5) {
            throw $e;
        }
        echo "Deadlock on attempt $attempt, retrying...\n";
        usleep(2000000);
    }
}

// Step 4: Run artisan migrate
putenv('APP_ENV=testing');
putenv('DB_CONNECTION=pgsql');
putenv('DB_DATABASE=jawla_test');

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

Artisan::call('migrate', ['--force' => true]);
echo Artisan::output();

// Step 5: Terminate migration connections so tests start clean
$pdo = new PDO('pgsql:host=127.0.0.1;port=5432;dbname=jawla_test', 'postgres', 'postgres');
$stale = $pdo->query("SELECT pid FROM pg_stat_activity WHERE datname = 'jawla_test' AND pid <> pg_backend_pid()")->fetchAll();
foreach ($stale as $row) {
    $pdo->exec("SELECT pg_terminate_backend({$row['pid']})");
}
unset($pdo);
usleep(1000000);
echo "Done\n";
