<?php

namespace App\Policies;

use App\Models\User;

class DailyVisitAssignmentPolicy
{
    public function viewAny(User $u): bool
    {
        return $u->can('view_any:daily_visit_assignment');
    }

    public function view(User $u): bool
    {
        return $u->can('view:daily_visit_assignment');
    }

    public function create(User $u): bool
    {
        return $u->can('create:daily_visit_assignment');
    }

    public function update(User $u): bool
    {
        return $u->can('update:daily_visit_assignment');
    }

    public function delete(User $u): bool
    {
        return $u->can('delete:daily_visit_assignment');
    }
}
