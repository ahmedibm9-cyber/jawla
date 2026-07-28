<?php

namespace App\Policies;

use App\Models\Stock;
use App\Models\User;
use App\Policies\Concerns\ChecksCompanyOwnership;

class StockPolicy
{
    use ChecksCompanyOwnership;

    public function viewAny(User $u): bool
    {
        return $u->can('view_any:stock');
    }

    public function view(User $u, Stock $stock): bool
    {
        return $u->can('view:stock') && $this->matchesCompany($u, $stock);
    }

    public function adjust(User $u, Stock $stock): bool
    {
        return $u->can('stock.adjust');
    }

    public function import(User $u): bool
    {
        return $u->can('stock.import');
    }
}
