<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
echo 'Companies: ' . DB::table('companies')->count() . PHP_EOL;
DB::purge();
