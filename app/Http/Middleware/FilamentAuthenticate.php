<?php

namespace App\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Filament\Models\Contracts\FilamentUser;
use Illuminate\Auth\Middleware\Authenticate as Middleware;

class FilamentAuthenticate extends Middleware
{
    public function handle($request, Closure $next, ...$guards): mixed
    {
        $this->authenticate($request, $guards);

        $user = Filament::auth()->user();

        if ($user instanceof FilamentUser && ! $user->canAccessPanel(Filament::getCurrentOrDefaultPanel())) {
            if ($user->hasAnyRole(['sales_rep', 'rep'])) {
                return redirect('/app');
            }

            return redirect()->route('login')->with('error', 'ليس لديك صلاحية الوصول إلى لوحة التحكم.');
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
