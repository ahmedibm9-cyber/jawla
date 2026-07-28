<?php

namespace App\Policies;

use App\Models\PriceQuotationRequest;
use App\Models\User;
use App\Policies\Concerns\ChecksCompanyOwnership;

class PriceQuotationRequestPolicy
{
    use ChecksCompanyOwnership;

    public function viewAny(User $u): bool
    {
        return $u->can('view_any:price_quotation_request');
    }

    public function view(User $u, PriceQuotationRequest $model): bool
    {
        return $u->can('view:price_quotation_request') && $this->matchesCompany($u, $model);
    }

    public function create(User $u): bool
    {
        return $u->can('create:price_quotation_request');
    }

    public function update(User $u, PriceQuotationRequest $model): bool
    {
        return $u->can('update:price_quotation_request') && $this->matchesCompany($u, $model);
    }

    public function delete(User $u, PriceQuotationRequest $model): bool
    {
        return $u->can('delete:price_quotation_request') && $this->matchesCompany($u, $model);
    }
}
