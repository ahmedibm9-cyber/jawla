<?php

namespace App\Policies;

use App\Models\PurchaseRequest;
use App\Models\User;
use App\Policies\Concerns\ChecksCompanyOwnership;

class PurchaseRequestPolicy
{
    use ChecksCompanyOwnership;

    public function viewAny(User $u): bool
    {
        return $u->can('view_any:purchase_request');
    }

    public function view(User $u, PurchaseRequest $model): bool
    {
        return $u->can('view:purchase_request') && $this->matchesCompany($u, $model);
    }

    public function create(User $u): bool
    {
        return $u->can('create:purchase_request');
    }

    public function update(User $u, PurchaseRequest $model): bool
    {
        return $u->can('update:purchase_request') && $this->matchesCompany($u, $model);
    }

    public function delete(User $u, PurchaseRequest $model): bool
    {
        return $u->can('delete:purchase_request') && $this->matchesCompany($u, $model);
    }
}
