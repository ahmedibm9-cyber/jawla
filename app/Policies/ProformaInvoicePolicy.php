<?php

namespace App\Policies;

use App\Models\ProformaInvoice;
use App\Models\User;
use App\Policies\Concerns\ChecksCompanyOwnership;

class ProformaInvoicePolicy
{
    use ChecksCompanyOwnership;

    public function viewAny(User $u): bool
    {
        return $u->can('view_any:proforma_invoice');
    }

    public function view(User $u, ProformaInvoice $model): bool
    {
        return $u->can('view:proforma_invoice') && $this->matchesCompany($u, $model);
    }

    public function create(User $u): bool
    {
        return $u->can('create:proforma_invoice');
    }

    public function update(User $u, ProformaInvoice $model): bool
    {
        return $u->can('update:proforma_invoice') && $this->matchesCompany($u, $model);
    }

    public function delete(User $u, ProformaInvoice $model): bool
    {
        return $u->can('delete:proforma_invoice') && $this->matchesCompany($u, $model);
    }
}
