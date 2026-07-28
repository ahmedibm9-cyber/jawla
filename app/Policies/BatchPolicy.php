<?php

namespace App\Policies;

use App\Models\Batch;
use App\Models\User;
use App\Policies\Concerns\ChecksCompanyOwnership;

class BatchPolicy
{
    use ChecksCompanyOwnership;

    public function viewAny(User $u): bool
    {
        return $u->can('view_any:batch');
    }

    public function view(User $u, Batch $model): bool
    {
        return $u->can('view:batch') && $this->matchesCompany($u, $model);
    }

    public function create(User $u): bool
    {
        return $u->can('create:batch');
    }

    public function update(User $u, Batch $model): bool
    {
        return $u->can('update:batch') && $this->matchesCompany($u, $model);
    }

    public function delete(User $u, Batch $model): bool
    {
        return $u->can('delete:batch') && $this->matchesCompany($u, $model);
    }
}
