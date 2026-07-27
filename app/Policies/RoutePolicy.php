<?php

namespace App\Policies;

use App\Models\User;

class RoutePolicy
{
    public function viewAny(User $u): bool
    {
        return $u->can('view_any:route');
    }

    public function view(User $u): bool
    {
        return $u->can('view:route');
    }

    public function create(User $u): bool
    {
        return $u->can('create:route');
    }

    public function update(User $u): bool
    {
        return $u->can('update:route');
    }

    public function delete(User $u): bool
    {
        return $u->can('delete:route');
    }
}
