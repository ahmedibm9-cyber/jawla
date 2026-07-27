<?php

namespace App\Policies;

use App\Models\User;

class ProformaInvoicePolicy
{
    public function viewAny(User $u): bool
    {
        return $u->can('view_any:proforma_invoice');
    }

    public function view(User $u): bool
    {
        return $u->can('view:proforma_invoice');
    }

    public function create(User $u): bool
    {
        return $u->can('create:proforma_invoice');
    }

    public function update(User $u): bool
    {
        return $u->can('update:proforma_invoice');
    }

    public function delete(User $u): bool
    {
        return $u->can('delete:proforma_invoice');
    }
}
