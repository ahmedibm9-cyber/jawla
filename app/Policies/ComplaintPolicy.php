<?php

namespace App\Policies;

use App\Models\Complaint;
use App\Models\User;
use App\Policies\Concerns\ChecksCompanyOwnership;

class ComplaintPolicy
{
    use ChecksCompanyOwnership;

    public function viewAny(User $u): bool
    {
        return $u->can('view_any:complaint');
    }

    public function view(User $u, Complaint $model): bool
    {
        return $u->can('view:complaint') && $this->matchesCompany($u, $model);
    }

    public function create(User $u): bool
    {
        return $u->can('create:complaint');
    }

    public function update(User $u, Complaint $model): bool
    {
        return $u->can('update:complaint') && $this->matchesCompany($u, $model);
    }

    public function delete(User $u, Complaint $model): bool
    {
        return $u->can('delete:complaint') && $this->matchesCompany($u, $model);
    }
}
