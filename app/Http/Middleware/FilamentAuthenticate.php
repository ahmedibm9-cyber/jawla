<?php

namespace App\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Auth\Middleware\Authenticate as Middleware;

class FilamentAuthenticate extends Middleware
{
    public function handle($request, Closure $next, ...$guards): mixed
    {
        $this->authenticate($request, $guards);

        $user = $request->user();

        // Redirect pure reps to the PWA. Multi-role users (e.g. admin + rep)
        // keep admin-panel access — canAccessPanel() already allows all roles.
        if ($user && ! $request->hasHeader('X-Livewire')) {
            $repRoles = ['sales_rep', 'rep'];
            $hasOnlyRepRoles = $user->getRoleNames()->every(fn (string $r) => in_array($r, $repRoles, true));

            if ($hasOnlyRepRoles) {
                return redirect('/app');
            }
        }

        return $next($request);
    }

    protected function authenticate($request, array $guards): void
    {
        $guard = Filament::auth();

        if (! $guard->check()) {
            $this->unauthenticated($request, $guards);

            return;
        }

        $this->auth->shouldUse(Filament::getAuthGuard());
    }

    protected function redirectTo($request): ?string
    {
        return route('login');
    }
}
