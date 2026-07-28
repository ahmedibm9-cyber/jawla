<?php

namespace App\Policies;

use App\Models\ReturnRecord;
use App\Models\User;
use App\Policies\Concerns\ChecksCompanyOwnership;

class ReturnRecordPolicy
{
    use ChecksCompanyOwnership;

    public function viewAny(User $u): bool
    {
        return $u->can('view_any:return_record');
    }

    public function view(User $u, ReturnRecord $model): bool
    {
        return $u->can('view:return_record') && $this->matchesCompany($u, $model);
    }

    public function create(User $u): bool
    {
        return $u->can('create:return_record');
    }

    public function update(User $u, ReturnRecord $model): bool
    {
        return $u->can('update:return_record') && $this->matchesCompany($u, $model);
    }

    public function delete(User $u, ReturnRecord $model): bool
    {
        return $u->can('delete:return_record') && $this->matchesCompany($u, $model);
    }
}
