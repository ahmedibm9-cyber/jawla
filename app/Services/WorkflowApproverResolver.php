<?php

namespace App\Services;

use App\Models\User;

class WorkflowApproverResolver
{
    public function forCompany(int $companyId): User
    {
        $activeCompanyUsers = User::query()
            ->where('company_id', $companyId)
            ->where('is_active', true);

        $approver = (clone $activeCompanyUsers)
            ->whereHas('roles', fn ($roles) => $roles->where('name', 'sales_manager'))
            ->first()
            ?? $activeCompanyUsers
                ->whereHas('roles', fn ($roles) => $roles->where('name', 'admin'))
                ->first();

        throw_if($approver === null, new \DomainException(
            'An active sales manager or administrator is required for this workflow.',
        ));

        return $approver;
    }
}
