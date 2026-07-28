<?php

namespace App\Policies;

use App\Models\Alarm;
use App\Models\User;
use App\Policies\Concerns\ChecksCompanyOwnership;

class AlarmPolicy
{
    use ChecksCompanyOwnership;

    public function viewAny(User $u): bool
    {
        return $u->can('view_any:alarm');
    }

    public function view(User $u, Alarm $model): bool
    {
        return $u->can('view:alarm') && $this->matchesCompany($u, $model);
    }

    public function create(User $u): bool
    {
        return $u->can('create:alarm');
    }

    public function update(User $u, Alarm $model): bool
    {
        return $u->can('update:alarm') && $this->matchesCompany($u, $model);
    }

    public function delete(User $u, Alarm $model): bool
    {
        return $u->can('delete:alarm') && $this->matchesCompany($u, $model);
    }
}
