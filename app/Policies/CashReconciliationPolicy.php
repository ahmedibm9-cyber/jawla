<?php

namespace App\Policies;

use App\Models\User;

class CashReconciliationPolicy
{
    public function viewAny(User $u): bool
    {
        return $u->can('view_any:cash_reconciliation');
    }

    public function view(User $u): bool
    {
        return $u->can('view:cash_reconciliation');
    }

    public function create(User $u): bool
    {
        return false; // created only by reps via the PWA service
    }

    public function update(User $u): bool
    {
        return $u->can('update:cash_reconciliation');
    }

    public function delete(User $u): bool
    {
        return $u->can('delete:cash_reconciliation');
    }
}
