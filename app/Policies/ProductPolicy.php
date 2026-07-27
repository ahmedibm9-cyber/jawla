<?php

namespace App\Policies;

use App\Models\User;

class ProductPolicy
{
    public function viewAny(User $u): bool
    {
        return $u->can('view_any:product');
    }

    public function view(User $u): bool
    {
        return true;
    }

    public function create(User $u): bool
    {
        return $u->can('create:product');
    }

    public function update(User $u): bool
    {
        return $u->can('update:product');
    }

    public function delete(User $u): bool
    {
        return $u->can('delete:product');
    }
}
