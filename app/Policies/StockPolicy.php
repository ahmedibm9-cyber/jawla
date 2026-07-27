<?php

namespace App\Policies;

use App\Models\Stock;
use App\Models\User;

class StockPolicy
{
    public function viewAny(User $u): bool
    {
        return $u->can('view_any:stock');
    }

    public function view(User $u, Stock $stock): bool
    {
        return $u->can('view:stock');
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
