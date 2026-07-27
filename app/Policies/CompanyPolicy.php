<?php

namespace App\Policies;

use App\Models\User;

class CompanyPolicy
{
    public function viewAny(User $u): bool
    {
        return $u->can('view_any:company');
    }

    public function view(User $u): bool
    {
        return $u->can('view:company');
    }

    public function create(User $u): bool
    {
        return $u->can('create:company');
    }

    public function update(User $u): bool
    {
        return $u->can('update:company');
    }

    public function delete(User $u): bool
    {
        return $u->can('delete:company');
    }
}
