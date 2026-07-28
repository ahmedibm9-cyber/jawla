<?php

namespace App\Policies;

use App\Models\Company;
use App\Models\User;
use App\Policies\Concerns\ChecksCompanyOwnership;

class CompanyPolicy
{
    use ChecksCompanyOwnership;

    public function viewAny(User $u): bool
    {
        return $u->can('view_any:company');
    }

    public function view(User $u, Company $model): bool
    {
        return $u->can('view:company') && $this->matchesCompany($u, $model);
    }

    public function create(User $u): bool
    {
        return $u->can('create:company');
    }

    public function update(User $u, Company $model): bool
    {
        return $u->can('update:company') && $this->matchesCompany($u, $model);
    }

    public function delete(User $u, Company $model): bool
    {
        return $u->can('delete:company') && $this->matchesCompany($u, $model);
    }
}
