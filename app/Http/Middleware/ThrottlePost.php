<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class ThrottlePost
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('POST')) {
            $user = $request->user();
            $key = 'post|'.($user ? 'user:'.$user->id : 'ip:'.$request->ip());

            if (RateLimiter::tooManyAttempts($key, 60)) {
                return response()->json([
                    'message' => __('errors.429'),
                ], 429);
            }

            RateLimiter::hit($key, 60);
        }

        return $next($request);
    }
}
