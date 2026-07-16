<?php

namespace App\Policies;

use App\Models\Stock;
use App\Models\User;

class StockPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'warehouse_keeper', 'sales_manager', 'accounts']);
    }

    public function view(User $user, Stock $stock): bool
    {
        return $user->hasAnyRole(['admin', 'warehouse_keeper', 'sales_manager', 'accounts']);
    }

    public function adjust(User $user, Stock $stock): bool
    {
        return $user->hasAnyRole(['admin', 'warehouse_keeper']);
    }

    public function import(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'warehouse_keeper']);
    }
}
