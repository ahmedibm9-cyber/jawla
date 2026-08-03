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
        if ($request->user() === null || config('jawla.is_demo')) {
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
