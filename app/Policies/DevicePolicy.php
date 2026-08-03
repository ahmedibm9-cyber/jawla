<?php

namespace App\Policies;

use App\Models\Device;
use App\Models\User;
use App\Policies\Concerns\ChecksCompanyOwnership;

class DevicePolicy
{
    use ChecksCompanyOwnership;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any:device');
    }

    public function view(User $user, Device $device): bool
    {
        return $user->can('view:device') && $this->matchesCompany($user, $device);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Device $device): bool
    {
        return $user->can('devices.approve') && $this->matchesCompany($user, $device);
    }

    public function delete(User $user, Device $device): bool
    {
        return false;
    }
}
