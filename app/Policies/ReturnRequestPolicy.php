<?php

namespace App\Policies;

use App\Models\ReturnRequest;
use App\Models\User;
use App\Policies\Concerns\ChecksCompanyOwnership;

class ReturnRequestPolicy
{
    use ChecksCompanyOwnership;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any:return_request');
    }

    public function view(User $user, ReturnRequest $record): bool
    {
        return $user->can('view:return_request') && $this->matchesCompany($user, $record);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, ReturnRequest $record): bool
    {
        return ($user->can('return_requests.approve') || $user->can('return_requests.receive')) && $this->matchesCompany($user, $record);
    }

    public function delete(User $user, ReturnRequest $record): bool
    {
        return false;
    }
}
