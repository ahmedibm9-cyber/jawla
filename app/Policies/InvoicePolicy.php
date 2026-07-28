<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;
use App\Policies\Concerns\ChecksCompanyOwnership;

class InvoicePolicy
{
    use ChecksCompanyOwnership;

    public function viewAny(User $u): bool
    {
        return $u->can('view_any:invoice');
    }

    public function view(User $u, Invoice $model): bool
    {
        return $u->can('view:invoice') && $this->matchesCompany($u, $model);
    }

    public function create(User $u): bool
    {
        return $u->can('create:invoice');
    }

    public function update(User $u, Invoice $model): bool
    {
        return $u->can('update:invoice') && $this->matchesCompany($u, $model);
    }

    public function delete(User $u, Invoice $model): bool
    {
        return $u->can('delete:invoice') && $this->matchesCompany($u, $model);
    }
}
