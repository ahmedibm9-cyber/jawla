<?php

namespace App\Policies;

use App\Models\OrganizationUnit;
use App\Models\User;
use App\Policies\Concerns\ChecksCompanyOwnership;

class OrganizationUnitPolicy
{
    use ChecksCompanyOwnership;

    public function viewAny(User $user): bool { return $user->can('view_any:organization_unit'); }
    public function view(User $user, OrganizationUnit $unit): bool { return $user->can('view:organization_unit') && $this->matchesCompany($user, $unit); }
    public function create(User $user): bool { return $user->can('create:organization_unit'); }
    public function update(User $user, OrganizationUnit $unit): bool { return $user->can('update:organization_unit') && $this->matchesCompany($user, $unit); }
    public function delete(User $user, OrganizationUnit $unit): bool { return $user->can('delete:organization_unit') && $this->matchesCompany($user, $unit); }
}
