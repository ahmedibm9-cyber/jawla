<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Policies\Concerns\ChecksCompanyOwnership;
use Spatie\Permission\Models\Role;

class RolePolicy
{
    use ChecksCompanyOwnership;

    public function viewAny(User $u): bool
    {
        return $u->can('view_any:role');
    }

    public function view(User $u, Role $role): bool
    {
        return $u->can('view:role') && $this->matchesCompany($u, $role);
    }

    public function create(User $u): bool
    {
        return $u->can('create:role');
    }

    public function update(User $u, Role $role): bool
    {
        return $u->can('update:role') && $this->matchesCompany($u, $role);
    }

    public function delete(User $u, Role $role): bool
    {
        return $u->can('delete:role') && $this->matchesCompany($u, $role);
    }

    public function deleteAny(User $u): bool
    {
        return $u->can('delete_any:role');
    }

    public function forceDeleteAny(User $u): bool
    {
        return $u->can('force_delete_any:role');
    }

    public function restoreAny(User $u): bool
    {
        return $u->can('restore_any:role');
    }
}
