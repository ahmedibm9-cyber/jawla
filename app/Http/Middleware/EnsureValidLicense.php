<?php

namespace App\Http\Middleware;

use App\Services\LicenseService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureValidLicense
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() === null) {
            return $next($request);
        }

        // In demo mode, skip license checks — but NEVER in production.
        // If someone sets JAWLA_IS_DEMO=true on a production env, fail hard.
        if (config('jawla.is_demo')) {
            if (app()->isProduction()) {
                abort(500, 'Demo mode is not allowed in production.');
            }

            return $next($request);
        }

        try {
            app(LicenseService::class)->assertRuntimeValid();
        } catch (\DomainException) {
            if ($request->expectsJson()) {
                return response()->json(['message' => __('app.license_invalid')], 423);
            }

            return redirect()->route('license.recovery')->withErrors(['license' => __('app.license_invalid')]);
        }

        return $next($request);
    }
}
