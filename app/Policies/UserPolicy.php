<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $u): bool
    {
        return $u->can('view_any:user');
    }

    public function view(User $u, User $target): bool
    {
        if ($u->isSuperAdmin()) {
            return true;
        }

        return $u->can('view:user') && ! $target->isSuperAdmin();
    }

    public function create(User $u): bool
    {
        return $u->can('create:user');
    }

    public function update(User $u, User $target): bool
    {
        if ($u->isSuperAdmin()) {
            return true;
        }

        return $u->can('update:user') && ! $target->isSuperAdmin();
    }

    public function delete(User $u, User $target): bool
    {
        if ($u->isSuperAdmin()) {
            return true;
        }

        return $u->can('delete:user') && ! $target->isSuperAdmin();
    }
}
