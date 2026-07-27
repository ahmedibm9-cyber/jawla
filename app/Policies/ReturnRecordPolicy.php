<?php

namespace App\Policies;

use App\Models\User;

class ReturnRecordPolicy
{
    public function viewAny(User $u): bool
    {
        return $u->can('view_any:return_record');
    }

    public function view(User $u): bool
    {
        return $u->can('view:return_record');
    }

    public function create(User $u): bool
    {
        return $u->can('create:return_record');
    }

    public function update(User $u): bool
    {
        return $u->can('update:return_record');
    }

    public function delete(User $u): bool
    {
        return $u->can('delete:return_record');
    }
}
