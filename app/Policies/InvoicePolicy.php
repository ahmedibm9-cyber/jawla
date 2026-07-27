<?php

namespace App\Policies;

use App\Models\User;

class InvoicePolicy
{
    public function viewAny(User $u): bool
    {
        return $u->can('view_any:invoice');
    }

    public function view(User $u): bool
    {
        return $u->can('view:invoice');
    }

    public function create(User $u): bool
    {
        return $u->can('create:invoice');
    }

    public function update(User $u): bool
    {
        return $u->can('update:invoice');
    }

    public function delete(User $u): bool
    {
        return $u->can('delete:invoice');
    }
}
