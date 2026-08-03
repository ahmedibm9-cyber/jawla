<?php

namespace App\Policies;

use App\Models\SalesOrder;
use App\Models\User;
use App\Policies\Concerns\ChecksCompanyOwnership;

class SalesOrderPolicy
{
    use ChecksCompanyOwnership;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any:sales_order');
    }

    public function view(User $user, SalesOrder $record): bool
    {
        return $user->can('view:sales_order') && $this->matchesCompany($user, $record);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, SalesOrder $record): bool
    {
        return $user->can('sales_orders.approve') && $this->matchesCompany($user, $record);
    }

    public function delete(User $user, SalesOrder $record): bool
    {
        return false;
    }
}
