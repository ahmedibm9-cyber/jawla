<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRepRole
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        if (! $user->is_active || ! $user->hasAnyRole(['sales_rep', 'rep'])) {
            return redirect()->route('filament.admin.pages.dashboard');
        }

        return $next($request);
    }
}
