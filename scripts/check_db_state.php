<?php

$pdo = new PDO('pgsql:host=127.0.0.1;port=5432;dbname=postgres', 'postgres', 'postgres', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

echo '=== Databases ==='.PHP_EOL;
$rows = $pdo->query("SELECT datname, pg_database_size(datname) as size FROM pg_database WHERE datname LIKE 'jawla%' ORDER BY size DESC")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) {
    echo $r['datname'].': '.round($r['size'] / 1024 / 1024, 1).' MB'.PHP_EOL;
}

echo PHP_EOL.'=== Active connections ==='.PHP_EOL;
$rows = $pdo->query("SELECT datname, count(*) as cnt FROM pg_stat_activity WHERE datname LIKE 'jawla%' GROUP BY datname")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) {
    echo $r['datname'].': '.$r['cnt'].' connections'.PHP_EOL;
}

echo PHP_EOL.'=== PG memory settings ==='.PHP_EOL;
$rows = $pdo->query("SELECT name, setting, unit, short_desc FROM pg_settings WHERE name IN ('shared_buffers', 'work_mem', 'maintenance_work_mem', 'max_connections', 'effective_cache_size', 'temp_buffers', 'max_prepared_transactions') ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) {
    echo $r['name'].' = '.$r['setting'].' '.($r['unit'] ?? '').' — '.$r['short_desc'].PHP_EOL;
}

echo PHP_EOL.'=== OS memory ==='.PHP_EOL;
echo 'PHP memory_limit: '.ini_get('memory_limit').PHP_EOL;
echo 'PHP memory_get_usage: '.round(memory_get_usage(true) / 1024 / 1024, 1).' MB'.PHP_EOL;
echo 'PHP memory_get_peak: '.round(memory_get_peak_usage(true) / 1024 / 1024, 1).' MB'.PHP_EOL;
