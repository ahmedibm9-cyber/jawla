<?php

namespace App\Policies;

use App\Models\User;

class TaskPolicy
{
    public function viewAny(User $u): bool
    {
        return $u->can('view_any:task');
    }

    public function view(User $u): bool
    {
        return true;
    }

    public function create(User $u): bool
    {
        return $u->can('create:task');
    }

    public function update(User $u): bool
    {
        return $u->can('update:task');
    }

    public function delete(User $u): bool
    {
        return $u->can('delete:task');
    }
}
