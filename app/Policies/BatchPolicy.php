<?php

namespace App\Policies;

use App\Models\User;

class BatchPolicy
{
    public function viewAny(User $u): bool
    {
        return $u->hasAnyRole(['admin', 'warehouse_keeper', 'purchasing']);
    }

    public function view(User $u): bool
    {
        return $u->hasAnyRole(['admin', 'warehouse_keeper', 'purchasing']);
    }

    public function create(User $u): bool
    {
        return $u->hasAnyRole(['admin', 'warehouse_keeper']);
    }

    public function update(User $u): bool
    {
        return $u->hasAnyRole(['admin', 'warehouse_keeper']);
    }

    public function delete(User $u): bool
    {
        return $u->hasRole('admin');
    }
}
