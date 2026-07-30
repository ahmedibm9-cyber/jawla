<?php

namespace App\Policies;

use App\Models\GoodsInTransit;
use App\Models\User;
use App\Policies\Concerns\ChecksCompanyOwnership;

class GoodsInTransitPolicy
{
    use ChecksCompanyOwnership;

    public function viewAny(User $u): bool
    {
        return $u->can('view_any:goods_in_transit');
    }

    public function view(User $u, GoodsInTransit $model): bool
    {
        return $u->can('view:goods_in_transit') && $this->matchesCompany($u, $model);
    }

    public function create(User $u): bool
    {
        return $u->can('create:goods_in_transit');
    }

    public function update(User $u, GoodsInTransit $model): bool
    {
        return $u->can('update:goods_in_transit') && $this->matchesCompany($u, $model);
    }

    public function delete(User $u, GoodsInTransit $model): bool
    {
        return $u->can('delete:goods_in_transit') && $this->matchesCompany($u, $model);
    }
}
