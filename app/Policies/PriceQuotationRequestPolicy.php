<?php

namespace App\Policies;

use App\Models\User;

class PriceQuotationRequestPolicy
{
    public function viewAny(User $u): bool
    {
        return $u->can('view_any:price_quotation_request');
    }

    public function view(User $u): bool
    {
        return $u->can('view:price_quotation_request');
    }

    public function create(User $u): bool
    {
        return $u->can('create:price_quotation_request');
    }

    public function update(User $u): bool
    {
        return $u->can('update:price_quotation_request');
    }

    public function delete(User $u): bool
    {
        return $u->can('delete:price_quotation_request');
    }
}
