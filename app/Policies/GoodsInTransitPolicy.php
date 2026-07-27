<?php

namespace App\Policies;

use App\Models\User;

class GoodsInTransitPolicy
{
    public function viewAny(User $u): bool
    {
        return $u->can('view_any:goods_in_transit');
    }

    public function view(User $u): bool
    {
        return true;
    }

    public function create(User $u): bool
    {
        return $u->can('create:goods_in_transit');
    }

    public function update(User $u): bool
    {
        return $u->can('update:goods_in_transit');
    }

    public function delete(User $u): bool
    {
        return $u->can('delete:goods_in_transit');
    }
}
