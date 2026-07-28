<?php

namespace App\Policies;

use App\Models\Expense;
use App\Models\User;
use App\Policies\Concerns\ChecksCompanyOwnership;

class ExpensePolicy
{
    use ChecksCompanyOwnership;

    public function viewAny(User $u): bool
    {
        return $u->can('view_any:expense');
    }

    public function view(User $u, Expense $model): bool
    {
        return $u->can('view:expense') && $this->matchesCompany($u, $model);
    }

    public function create(User $u): bool
    {
        return $u->can('create:expense');
    }

    public function update(User $u, Expense $model): bool
    {
        return $u->can('update:expense') && $this->matchesCompany($u, $model);
    }

    public function delete(User $u, Expense $model): bool
    {
        return $u->can('delete:expense') && $this->matchesCompany($u, $model);
    }
}
