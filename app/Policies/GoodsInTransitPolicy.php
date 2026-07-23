<?php

namespace App\Policies;

use App\Models\User;

class GoodsInTransitPolicy
{
    public function viewAny(User $u): bool
    {
        return $u->hasAnyRole(['admin', 'sales_manager', 'purchasing', 'warehouse_keeper', 'executive']);
    }

    public function view(User $u): bool
    {
        return true;
    }

    public function create(User $u): bool
    {
        return $u->hasAnyRole(['admin', 'purchasing', 'warehouse_keeper']);
    }

    public function update(User $u): bool
    {
        return $u->hasAnyRole(['admin', 'purchasing', 'warehouse_keeper']);
    }

    public function delete(User $u): bool
    {
        return $u->hasRole('admin');
    }
}
