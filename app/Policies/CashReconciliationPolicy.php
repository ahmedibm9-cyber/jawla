<?php

namespace App\Policies;

use App\Models\CashReconciliation;
use App\Models\User;
use App\Policies\Concerns\ChecksCompanyOwnership;

class CashReconciliationPolicy
{
    use ChecksCompanyOwnership;

    public function viewAny(User $u): bool
    {
        return $u->can('view_any:cash_reconciliation');
    }

    public function view(User $u, CashReconciliation $model): bool
    {
        return $u->can('view:cash_reconciliation') && $this->matchesCompany($u, $model);
    }

    public function create(User $u): bool
    {
        return false; // created only by reps via the PWA service
    }

    public function update(User $u, CashReconciliation $model): bool
    {
        return $u->can('update:cash_reconciliation') && $this->matchesCompany($u, $model);
    }

    public function delete(User $u, CashReconciliation $model): bool
    {
        return $u->can('delete:cash_reconciliation') && $this->matchesCompany($u, $model);
    }
}
