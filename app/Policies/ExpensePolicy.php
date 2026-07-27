<?php

namespace App\Policies;

use App\Models\User;

class ExpensePolicy
{
    public function viewAny(User $u): bool
    {
        return $u->can('view_any:expense');
    }

    public function view(User $u): bool
    {
        return $u->can('view:expense');
    }

    public function create(User $u): bool
    {
        return $u->can('create:expense');
    }

    public function update(User $u): bool
    {
        return $u->can('update:expense');
    }

    public function delete(User $u): bool
    {
        return $u->can('delete:expense');
    }
}
