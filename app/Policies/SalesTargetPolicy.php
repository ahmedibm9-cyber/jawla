<?php

namespace App\Policies;

use App\Models\User;

class SalesTargetPolicy
{
    public function viewAny(User $u): bool
    {
        return $u->hasAnyRole(['admin', 'sales_manager', 'executive']);
    }

    public function view(User $u): bool
    {
        return $u->hasAnyRole(['admin', 'sales_manager', 'executive']);
    }

    public function create(User $u): bool
    {
        return $u->hasAnyRole(['admin', 'sales_manager']);
    }

    public function update(User $u): bool
    {
        return $u->hasAnyRole(['admin', 'sales_manager']);
    }

    public function delete(User $u): bool
    {
        return $u->hasAnyRole(['admin', 'sales_manager']);
    }
}
