<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'],
    'allowed_origins' => array_filter([
        env('APP_URL', 'http://localhost'),
        env('APP_STAGING_URL'),
    ]),
    'allowed_origins_patterns' => [],
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
