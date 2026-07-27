<?php

namespace App\Policies;

use App\Models\User;

class ComplaintPolicy
{
    public function viewAny(User $u): bool
    {
        return $u->can('view_any:complaint');
    }

    public function view(User $u): bool
    {
        return $u->can('view:complaint');
    }

    public function create(User $u): bool
    {
        return $u->can('create:complaint');
    }

    public function update(User $u): bool
    {
        return $u->can('update:complaint');
    }

    public function delete(User $u): bool
    {
        return $u->can('delete:complaint');
    }
}
