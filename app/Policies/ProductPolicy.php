<?php

namespace App\Policies;

use App\Models\User;

class ProductPolicy
{
    public function viewAny(User $u): bool
    {
        return $u->hasAnyRole(['admin', 'sales_manager', 'accounts', 'purchasing', 'warehouse_keeper', 'executive', 'rep']);
    }

    public function view(User $u): bool
    {
        return true;
    }

    public function create(User $u): bool
    {
        return $u->hasAnyRole(['admin', 'accounts']);
    }

    public function update(User $u): bool
    {
        return $u->hasAnyRole(['admin', 'accounts']);
    }

    public function delete(User $u): bool
    {
        return $u->hasRole('admin');
    }
}
