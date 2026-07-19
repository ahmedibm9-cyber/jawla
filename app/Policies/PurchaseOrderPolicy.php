<?php

namespace App\Policies;

use App\Models\User;

class PurchaseOrderPolicy
{
    public function viewAny(User $u): bool
    {
        return $u->hasAnyRole(['admin', 'sales_manager', 'purchasing']);
    }

    public function view(User $u): bool
    {
        return $u->hasAnyRole(['admin', 'sales_manager', 'purchasing']);
    }

    // POs are only created by PurchaseRequestService on purchasing approval
    // and stay read-only in V1 (receiving/editing arrives with goods-in-transit).
    public function create(User $u): bool
    {
        return false;
    }

    public function update(User $u): bool
    {
        return false;
    }

    public function delete(User $u): bool
    {
        return false;
    }
}
