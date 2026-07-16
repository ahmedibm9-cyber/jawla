<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $u): bool
    {
        return $u->hasRole('admin');
    }

    public function view(User $u): bool
    {
        return $u->hasRole('admin');
    }

    public function create(User $u): bool
    {
        return $u->hasRole('admin');
    }

    public function update(User $u): bool
    {
        return $u->hasRole('admin');
    }

    public function delete(User $u): bool
    {
        return $u->hasRole('admin');
    }
}
