<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Requires recent password confirmation for high-risk actions.
 * The user must have confirmed their password within the last 4 hours.
 * Uses the session timestamp set by Filament's password confirmation.
 */
class EnsureRecentPasswordConfirmation
{
    private const CONFIRMATION_TTL = 4 * 3600; // 4 hours

    public function handle(Request $request, Closure $next): Response
    {
        $confirmedAt = session('auth.password_confirmed_at', 0);

        if (time() - $confirmedAt > self::CONFIRMATION_TTL) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Password confirmation required.',
                    'confirm_url' => route('filament.admin.auth.password.confirm'),
                ], 403);
            }

            return redirect()->route('filament.admin.auth.password.confirm');
        }

        return $next($request);
    }
}
