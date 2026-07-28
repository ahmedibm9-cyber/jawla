<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VanTransfer;
use App\Policies\Concerns\ChecksCompanyOwnership;

class VanTransferPolicy
{
    use ChecksCompanyOwnership;

    public function viewAny(User $u): bool
    {
        return $u->can('view_any:van_transfer');
    }

    public function view(User $u, VanTransfer $model): bool
    {
        return $u->can('view:van_transfer') && $this->matchesCompany($u, $model);
    }

    public function create(User $u): bool
    {
        return $u->can('create:van_transfer');
    }

    public function update(User $u, VanTransfer $model): bool
    {
        return $u->can('update:van_transfer') && $this->matchesCompany($u, $model);
    }

    public function delete(User $u, VanTransfer $model): bool
    {
        return $u->can('delete:van_transfer') && $this->matchesCompany($u, $model);
    }
}
