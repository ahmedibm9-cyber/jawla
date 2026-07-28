<?php

$mode = strtolower((string) env('JAWLA_MODE', 'production'));

if (! in_array($mode, ['production', 'demo'], true)) {
    $mode = 'production';
}

return [
    'mode' => $mode,
    'is_demo' => $mode === 'demo',
    'stock_import' => [
        'preview_ttl_minutes' => (int) env('JAWLA_STOCK_IMPORT_PREVIEW_TTL', 15),
        'large_variance_threshold' => (string) env('JAWLA_STOCK_IMPORT_LARGE_VARIANCE', '1000.000'),
    ],
    'retention' => [
        'location_pings_days' => (int) env('JAWLA_RETENTION_LOCATION_PINGS', 90),
        'activity_logs_days' => (int) env('JAWLA_RETENTION_ACTIVITY_LOGS', 365),
        'sessions_days' => (int) env('JAWLA_RETENTION_SESSIONS', 30),
    ],
];
