<?php

namespace App\Policies;

use App\Models\User;

class CustomerPolicy
{
    public function viewAny(User $u): bool
    {
        return $u->can('view_any:customer');
    }

    public function view(User $u): bool
    {
        return true;
    }

    public function create(User $u): bool
    {
        return $u->can('create:customer');
    }

    public function update(User $u): bool
    {
        return $u->can('update:customer');
    }

    public function delete(User $u): bool
    {
        return $u->can('delete:customer');
    }
}
