<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::before(function (User $user) {
            if ($user->isSuperAdmin()) {
                return true;
            }
        });

        Gate::define('products.manage_prices', fn (User $user) => $user->hasAnyRole(['admin', 'accounts', 'sales_manager', 'executive']));

        Gate::define('products.view_cost', fn (User $user) => $user->hasAnyRole(['admin', 'accounts', 'executive', 'sales_manager']));
    }
}
