<?php
namespace App\Policies;

use App\Models\User;

class CompanyPolicy
{
    public function viewAny(User $u): bool { return $this->access($u); }
    public function view(User $u): bool { return $this->access($u); }
    public function create(User $u): bool { return $u->hasRole('admin'); }
    public function update(User $u): bool { return $u->hasRole('admin'); }
    public function delete(User $u): bool { return $u->hasRole('admin'); }
    private function access(User $u): bool { return $u->hasAnyRole(['admin', 'executive']); }
}