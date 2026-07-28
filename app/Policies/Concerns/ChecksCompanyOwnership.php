<?php

namespace App\Policies\Concerns;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

trait ChecksCompanyOwnership
{
    protected function matchesCompany(User $user, Model $model): bool
    {
        $companyId = $model instanceof Company
            ? $model->getKey()
            : $model->getAttribute('company_id');

        return $companyId !== null
            && $user->activeCompanyId() === (int) $companyId;
    }
}
