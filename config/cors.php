<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'],
    'allowed_origins' => array_filter([
        env('APP_URL', 'http://localhost'),
        env('APP_STAGING_URL'),
        env('APP_PRODUCTION_URL'),
    ]),
    // Railway subdomains — covers misconfigured or missing env vars.
    'allowed_origins_patterns' => ['~^https://jawla(-[a-z0-9-]+)?\.up\.railway\.app$~'],
    'allowed_headers' => [
        'Accept',
        'Authorization',
        'Content-Type',
        'X-Requested-With',
        'X-CSRF-TOKEN',
    ],
    'exposed_headers' => [],
    'max_age' => 86400,
    'supports_credentials' => true,
];
