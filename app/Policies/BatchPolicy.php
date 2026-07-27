<?php

namespace App\Policies;

use App\Models\User;

class BatchPolicy
{
    public function viewAny(User $u): bool
    {
        return $u->can('view_any:batch');
    }

    public function view(User $u): bool
    {
        return $u->can('view:batch');
    }

    public function create(User $u): bool
    {
        return $u->can('create:batch');
    }

    public function update(User $u): bool
    {
        return $u->can('update:batch');
    }

    public function delete(User $u): bool
    {
        return $u->can('delete:batch');
    }
}
