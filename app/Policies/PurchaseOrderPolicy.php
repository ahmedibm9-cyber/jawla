<?php

namespace App\Policies;

use App\Models\PurchaseOrder;
use App\Models\User;
use App\Policies\Concerns\ChecksCompanyOwnership;

class PurchaseOrderPolicy
{
    use ChecksCompanyOwnership;

    public function viewAny(User $u): bool
    {
        return $u->can('view_any:purchase_order');
    }

    public function view(User $u, PurchaseOrder $model): bool
    {
        return $u->can('view:purchase_order') && $this->matchesCompany($u, $model);
    }

    // POs are only created by PurchaseRequestService on purchasing approval
    // and stay read-only in V1 (receiving/editing arrives with goods-in-transit).
    public function create(User $u): bool
    {
        return false;
    }

    public function update(User $u, PurchaseOrder $model): bool
    {
        return false;
    }

    public function delete(User $u, PurchaseOrder $model): bool
    {
        return false;
    }
}
