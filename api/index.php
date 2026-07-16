<?php

/**
 * Vercel Serverless PHP Handler for Laravel
 * This file routes all incoming requests to the Laravel application
 */

// Set the base path
$basePath = dirname(__DIR__);

// Load the Laravel application
require_once $basePath . '/bootstrap/app.php';

// Get the Laravel application instance
$app = require_once $basePath . '/bootstrap/app.php';

// Create the kernel
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Handle the request
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

// Send the response
$response->send();

// Terminate the application
$kernel->terminate($request, $response);
