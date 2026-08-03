<?php

namespace App\Policies;

use App\Models\SalesOrder;
use App\Models\User;
use App\Policies\Concerns\ChecksCompanyOwnership;
use App\Services\OrganizationScopeService;

class SalesOrderPolicy
{
    use ChecksCompanyOwnership;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any:sales_order');
    }

    public function view(User $user, SalesOrder $record): bool
    {
        return $user->can('view:sales_order') && $this->matchesCompany($user, $record)
            && app(OrganizationScopeService::class)->canAccessUser($user, (int) $record->user_id);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, SalesOrder $record): bool
    {
        return $user->can('sales_orders.approve') && $this->matchesCompany($user, $record)
            && app(OrganizationScopeService::class)->canAccessUser($user, (int) $record->user_id);
    }

    public function delete(User $user, SalesOrder $record): bool
    {
        return false;
    }
}
