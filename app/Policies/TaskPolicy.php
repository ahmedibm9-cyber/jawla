<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;
use App\Policies\Concerns\ChecksCompanyOwnership;

class TaskPolicy
{
    use ChecksCompanyOwnership;

    public function viewAny(User $u): bool
    {
        return $u->can('view_any:task');
    }

    public function view(User $u, Task $model): bool
    {
        return $this->matchesCompany($u, $model);
    }

    public function create(User $u): bool
    {
        return $u->can('create:task');
    }

    public function update(User $u, Task $model): bool
    {
        return $u->can('update:task') && $this->matchesCompany($u, $model);
    }

    public function delete(User $u, Task $model): bool
    {
        return $u->can('delete:task') && $this->matchesCompany($u, $model);
    }
}
