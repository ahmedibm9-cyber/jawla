<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;
use App\Policies\Concerns\ChecksCompanyOwnership;

class ProductPolicy
{
    use ChecksCompanyOwnership;

    public function viewAny(User $u): bool
    {
        return $u->can('view_any:product');
    }

    public function view(User $u, Product $model): bool
    {
        return $u->can('view:product') && $this->matchesCompany($u, $model);
    }

    public function create(User $u): bool
    {
        return $u->can('create:product');
    }

    public function update(User $u, Product $model): bool
    {
        return $u->can('update:product') && $this->matchesCompany($u, $model);
    }

    public function delete(User $u, Product $model): bool
    {
        return $u->can('delete:product') && $this->matchesCompany($u, $model);
    }
}
