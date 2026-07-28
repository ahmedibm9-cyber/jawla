<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\Stock;
use App\Models\User;
use App\Models\Warehouse;
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
        return $u->can('view:stock') && $this->belongsToActiveCompany($u, $stock);
    }

    public function adjust(User $u, Stock $stock): bool
    {
        return $u->can('stock.adjust') && $this->belongsToActiveCompany($u, $stock);
    }

    public function import(User $u): bool
    {
        return $u->can('stock.import');
    }

    private function belongsToActiveCompany(User $user, Stock $stock): bool
    {
        $companyId = $user->activeCompanyId();

        $warehouseMatches = Warehouse::withoutGlobalScopes()
            ->whereKey($stock->warehouse_id)
            ->where('company_id', $companyId)
            ->exists();

        $productMatches = Product::withoutGlobalScopes()
            ->whereKey($stock->product_id)
            ->where('company_id', $companyId)
            ->exists();

        return $warehouseMatches && $productMatches;
    }
}
