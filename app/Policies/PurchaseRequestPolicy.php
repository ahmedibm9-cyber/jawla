<?php

namespace App\Policies;

use App\Models\User;

class PurchaseRequestPolicy
{
    public function viewAny(User $u): bool
    {
        return $u->can('view_any:purchase_request');
    }

    public function view(User $u): bool
    {
        return $u->can('view:purchase_request');
    }

    public function create(User $u): bool
    {
        return $u->can('create:purchase_request');
    }

    public function update(User $u): bool
    {
        return $u->can('update:purchase_request');
    }

    public function delete(User $u): bool
    {
        return $u->can('delete:purchase_request');
    }
}
