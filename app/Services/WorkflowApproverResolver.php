<?php

namespace App\Services;

use App\Models\User;

class WorkflowApproverResolver
{
    public function forCompany(int $companyId): User
    {
        $approver = User::query()
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->whereHas('roles', fn ($roles) => $roles->whereIn('name', ['sales_manager', 'admin']))
            ->orderByRaw("case when exists (select 1 from model_has_roles mr join roles r on r.id = mr.role_id where mr.model_id = users.id and mr.model_type = ? and r.name = 'sales_manager') then 0 else 1 end", [User::class])
            ->first();

        throw_if($approver === null, new \DomainException(
            'An active sales manager or administrator is required for this workflow.',
        ));

        return $approver;
    }
}
