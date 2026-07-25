<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $u): bool
    {
        return $u->hasRole('admin');
    }

    public function view(User $u, User $target): bool
    {
        if ($u->isSuperAdmin()) {
            return true;
        }

        return $u->hasRole('admin') && ! $target->isSuperAdmin();
    }

    public function create(User $u): bool
    {
        return $u->hasRole('admin');
    }

    public function update(User $u, User $target): bool
    {
        if ($u->isSuperAdmin()) {
            return true;
        }

        return $u->hasRole('admin') && ! $target->isSuperAdmin();
    }

    public function delete(User $u, User $target): bool
    {
        if ($u->isSuperAdmin()) {
            return true;
        }

        return $u->hasRole('admin') && ! $target->isSuperAdmin();
    }
}
