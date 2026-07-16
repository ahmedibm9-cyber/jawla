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

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'ensure.rep' => EnsureRepRole::class,
        ]);

        $middleware->redirectGuestsTo('/app/login');

        $middleware->web(append: [
            SecurityHeaders::class,
            SetActiveCompanyContext::class,
            SetLocale::class,
            ThrottlePost::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
