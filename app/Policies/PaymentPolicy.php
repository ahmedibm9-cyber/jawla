<?php

namespace App\Policies;

use App\Models\User;

class PaymentPolicy
{
    public function viewAny(User $u): bool
    {
        return $u->can('view_any:payment');
    }

    public function view(User $u): bool
    {
        return $u->can('view:payment');
    }

    public function create(User $u): bool
    {
        return $u->can('create:payment');
    }

    public function update(User $u): bool
    {
        return $u->can('update:payment');
    }

    public function delete(User $u): bool
    {
        return $u->can('delete:payment');
    }
}
