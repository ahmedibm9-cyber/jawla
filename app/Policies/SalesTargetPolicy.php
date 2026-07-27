<?php

namespace App\Policies;

use App\Models\User;

class SalesTargetPolicy
{
    public function viewAny(User $u): bool
    {
        return $u->can('view_any:sales_target');
    }

    public function view(User $u): bool
    {
        return $u->can('view:sales_target');
    }

    public function create(User $u): bool
    {
        return $u->can('create:sales_target');
    }

    public function update(User $u): bool
    {
        return $u->can('update:sales_target');
    }

    public function delete(User $u): bool
    {
        return $u->can('delete:sales_target');
    }
}
