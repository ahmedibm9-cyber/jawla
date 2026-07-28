<?php

namespace App\Policies;

use App\Models\Route;
use App\Models\User;
use App\Policies\Concerns\ChecksCompanyOwnership;

class RoutePolicy
{
    use ChecksCompanyOwnership;

    public function viewAny(User $u): bool
    {
        return $u->can('view_any:route');
    }

    public function view(User $u, Route $model): bool
    {
        return $u->can('view:route') && $this->matchesCompany($u, $model);
    }

    public function create(User $u): bool
    {
        return $u->can('create:route');
    }

    public function update(User $u, Route $model): bool
    {
        return $u->can('update:route') && $this->matchesCompany($u, $model);
    }

    public function delete(User $u, Route $model): bool
    {
        return $u->can('delete:route') && $this->matchesCompany($u, $model);
    }
}
