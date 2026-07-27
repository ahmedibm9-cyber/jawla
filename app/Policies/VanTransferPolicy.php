<?php

namespace App\Policies;

use App\Models\User;

class VanTransferPolicy
{
    public function viewAny(User $u): bool
    {
        return $u->can('view_any:van_transfer');
    }

    public function view(User $u): bool
    {
        return $u->can('view:van_transfer');
    }

    public function create(User $u): bool
    {
        return $u->can('create:van_transfer');
    }

    public function update(User $u): bool
    {
        return $u->can('update:van_transfer');
    }

    public function delete(User $u): bool
    {
        return $u->can('delete:van_transfer');
    }
}
