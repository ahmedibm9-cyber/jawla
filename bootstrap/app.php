<?php

use App\Http\Middleware\EnsureRepRole;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetActiveCompanyContext;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\ThrottlePost;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Sentry\Laravel\Integration;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*', headers: Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_HOST | Request::HEADER_X_FORWARDED_PORT | Request::HEADER_X_FORWARDED_PROTO | Request::HEADER_X_FORWARDED_AWS_ELB);

        $middleware->alias([
            'ensure.rep' => EnsureRepRole::class,
        ]);

        // All unauthenticated users are redirected to the unified login page.
        $middleware->redirectGuestsTo('/login');

        $middleware->web(append: [
            SecurityHeaders::class,
            SetActiveCompanyContext::class,
            SetLocale::class,
            ThrottlePost::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->is('app/sync'),
        );

        // Guard the Sentry hook: if the package is absent from the built vendor
        // (e.g. a stale/cached deploy), an unguarded call here makes EVERY handled
        // exception fatal — turning proper 401/404/500 responses into raw 500s
        // app-wide. class_exists keeps error handling working with or without it.
        if (class_exists(Integration::class)) {
            Integration::handles($exceptions);
        }
    })->create();
