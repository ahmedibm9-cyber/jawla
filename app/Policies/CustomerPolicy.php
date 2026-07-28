<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;
use App\Policies\Concerns\ChecksCompanyOwnership;

class CustomerPolicy
{
    use ChecksCompanyOwnership;

    public function viewAny(User $u): bool
    {
        return $u->can('view_any:customer');
    }

    public function view(User $u, Customer $model): bool
    {
        return $this->matchesCompany($u, $model);
    }

    public function create(User $u): bool
    {
        return $u->can('create:customer');
    }

    public function update(User $u, Customer $model): bool
    {
        return $u->can('update:customer') && $this->matchesCompany($u, $model);
    }

    public function delete(User $u, Customer $model): bool
    {
        return $u->can('delete:customer') && $this->matchesCompany($u, $model);
    }
}
