<?php

namespace App\Policies;

use App\Models\User;

class VanTransferPolicy
{
    public function viewAny(User $u): bool
    {
        return $u->hasAnyRole(['admin', 'warehouse_keeper', 'sales_manager']);
    }

    public function view(User $u): bool
    {
        return $u->hasAnyRole(['admin', 'warehouse_keeper', 'sales_manager']);
    }

    public function create(User $u): bool
    {
        return $u->hasAnyRole(['admin', 'warehouse_keeper']);
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
