<?php

$pdo = new PDO('pgsql:host=127.0.0.1;port=5432;dbname=postgres', 'postgres', 'postgres');
$stale = $pdo->query("SELECT pid FROM pg_stat_activity WHERE datname = 'jawla_test'")->fetchAll();
foreach ($stale as $row) {
    $pdo->exec("SELECT pg_terminate_backend({$row['pid']})");
    echo "Killed PID {$row['pid']}\n";
}
sleep(2);
$pdo->exec('DROP DATABASE IF EXISTS jawla_test');
$pdo->exec('CREATE DATABASE jawla_test');
echo "DB recreated\n";
