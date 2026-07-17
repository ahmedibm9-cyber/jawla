<?php

namespace App\Policies;

use App\Models\User;

class ProformaInvoicePolicy
{
    public function viewAny(User $u): bool
    {
        return $u->hasAnyRole(['admin', 'sales_manager', 'accounts']);
    }

    public function view(User $u): bool
    {
        return $u->hasAnyRole(['admin', 'sales_manager', 'accounts']);
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
        return $u->hasRole('admin');
    }
}
