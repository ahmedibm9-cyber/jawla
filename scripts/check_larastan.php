<?php

use Composer\Autoload\ClassLoader;

require 'vendor/autoload.php';

$prefixes = (new ClassLoader)->getPrefixesPsr4();
$larastanPrefixes = array_filter(array_keys($prefixes), fn ($k) => str_contains($k, 'Larastan'));
echo 'Larastan PSR-4 prefixes found: '.count($larastanPrefixes).PHP_EOL;
foreach ($larastanPrefixes as $p) {
    echo "  $p => ".json_encode($prefixes[$p]).PHP_EOL;
}

echo 'class_exists Larastan\\Larastan\\SQL\\SqlParser: '.var_export(class_exists('Larastan\\Larastan\\SQL\\SqlParser'), true).PHP_EOL;
echo 'class_exists Larastan\\Larastan\\SQL\\IamcalSqlParser: '.var_export(class_exists('Larastan\\Larastan\\SQL\\IamcalSqlParser'), true).PHP_EOL;
echo 'interface_exists Larastan\\Larastan\\SQL\\SqlParser: '.var_export(interface_exists('Larastan\\Larastan\\SQL\\SqlParser'), true).PHP_EOL;
