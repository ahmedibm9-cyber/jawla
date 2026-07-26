<?php

$mode = strtolower((string) env('JAWLA_MODE', 'production'));

if (! in_array($mode, ['production', 'demo'], true)) {
    $mode = 'production';
}

return [
    'mode' => $mode,
    'is_demo' => $mode === 'demo',
];
