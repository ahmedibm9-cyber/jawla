<?php

/**
 * Vercel Serverless PHP Handler for Laravel
 * Routes all incoming requests through the Laravel HTTP Kernel
 */

$basePath = dirname(__DIR__);

// Bootstrap the Laravel application
$app = require_once $basePath . '/bootstrap/app.php';

// Get the HTTP kernel
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Handle the request and get the response
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

// Send the response to the client
$response->send();

// Perform application termination tasks
$kernel->terminate($request, $response);
