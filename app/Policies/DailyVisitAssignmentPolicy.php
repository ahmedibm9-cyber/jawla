<?php

namespace App\Policies;

use App\Models\DailyVisitAssignment;
use App\Models\User;
use App\Policies\Concerns\ChecksCompanyOwnership;

class DailyVisitAssignmentPolicy
{
    use ChecksCompanyOwnership;

    public function viewAny(User $u): bool
    {
        return $u->can('view_any:daily_visit_assignment');
    }

    public function view(User $u, DailyVisitAssignment $model): bool
    {
        return $u->can('view:daily_visit_assignment') && $this->matchesCompany($u, $model);
    }

    public function create(User $u): bool
    {
        return $u->can('create:daily_visit_assignment');
    }

    public function update(User $u, DailyVisitAssignment $model): bool
    {
        return $u->can('update:daily_visit_assignment') && $this->matchesCompany($u, $model);
    }

    public function delete(User $u, DailyVisitAssignment $model): bool
    {
        return $u->can('delete:daily_visit_assignment') && $this->matchesCompany($u, $model);
    }
}
