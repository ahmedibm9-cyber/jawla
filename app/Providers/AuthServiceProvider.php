<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::define('products.manage_prices', fn (User $user) => $user->can('products.manage_prices'));

        Gate::define('products.view_cost', fn (User $user) => $user->can('products.view_cost'));
    }
}
