<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;
use App\Policies\Concerns\ChecksCompanyOwnership;

class PaymentPolicy
{
    use ChecksCompanyOwnership;

    public function viewAny(User $u): bool
    {
        return $u->can('view_any:payment');
    }

    public function view(User $u, Payment $model): bool
    {
        return $u->can('view:payment') && $this->matchesCompany($u, $model);
    }

    public function create(User $u): bool
    {
        return $u->can('create:payment');
    }

    public function update(User $u, Payment $model): bool
    {
        return $u->can('update:payment') && $this->matchesCompany($u, $model);
    }

    public function delete(User $u, Payment $model): bool
    {
        return $u->can('delete:payment') && $this->matchesCompany($u, $model);
    }
}
