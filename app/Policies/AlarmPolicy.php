<?php

namespace App\Policies;

use App\Models\User;

class AlarmPolicy
{
    public function viewAny(User $u): bool
    {
        return $u->can('view_any:alarm');
    }

    public function view(User $u): bool
    {
        return $u->can('view:alarm');
    }

    public function create(User $u): bool
    {
        return $u->can('create:alarm');
    }

    public function update(User $u): bool
    {
        return $u->can('update:alarm');
    }

    public function delete(User $u): bool
    {
        return $u->can('delete:alarm');
    }
}
