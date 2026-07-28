<?php

namespace App\Policies\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

trait ChecksCompanyOwnership
{
    protected function matchesCompany(User $user, Model $model): bool
    {
        return (int) $user->company_id === (int) $model->company_id;
    }
}
