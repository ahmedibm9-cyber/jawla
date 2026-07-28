<?php

namespace App\Policies;

use App\Models\SalesTarget;
use App\Models\User;
use App\Policies\Concerns\ChecksCompanyOwnership;

class SalesTargetPolicy
{
    use ChecksCompanyOwnership;

    public function viewAny(User $u): bool
    {
        return $u->can('view_any:sales_target');
    }

    public function view(User $u, SalesTarget $model): bool
    {
        return $u->can('view:sales_target') && $this->matchesCompany($u, $model);
    }

    public function create(User $u): bool
    {
        return $u->can('create:sales_target');
    }

    public function update(User $u, SalesTarget $model): bool
    {
        return $u->can('update:sales_target') && $this->matchesCompany($u, $model);
    }

    public function delete(User $u, SalesTarget $model): bool
    {
        return $u->can('delete:sales_target') && $this->matchesCompany($u, $model);
    }
}
