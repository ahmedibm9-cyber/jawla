<?php

namespace App\Services;

use App\Enums\DeviceStatus;
use App\Models\Activity;
use App\Models\Device;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DeviceService
{
    /** @param array<string, mixed> $metadata */
    public function register(
        User $user,
        string $deviceUuid,
        string $name,
        ?string $platform = null,
        ?string $fingerprint = null,
        array $metadata = [],
    ): Device {
        throw_unless(Str::isUuid($deviceUuid), new \InvalidArgumentException('A valid device identifier is required.'));
        $name = trim($name);
        throw_if($name === '' || mb_strlen($name) > 255, new \InvalidArgumentException('A valid device name is required.'));

        return DB::transaction(function () use ($user, $deviceUuid, $name, $platform, $fingerprint, $metadata): Device {
            // ponytail: max devices per user, configurable via jawla.max_devices_per_user
            $maxDevices = (int) config('jawla.max_devices_per_user', 5);
            $existingCount = Device::query()
                ->where('user_id', $user->getKey())
                ->where('company_id', $user->activeCompanyId())
                ->where('device_uuid', '!=', $deviceUuid)
                ->count();
            if ($existingCount >= $maxDevices) {
                throw new \DomainException(
                    app()->getLocale() === 'ar'
                        ? "تم الوصول للحد الأقصى للأجهزة المسجلة ({$maxDevices})"
                        : "Device limit reached ({$maxDevices})"
                );
            }

            // ponytail: auto-approve only applies to NEW devices — existing unapproved
            // devices are NOT retroactively approved when require_approved_devices is toggled off.
            // This is intentional: admins must manually approve/reject pending devices.
            $autoApprove = ! (bool) $user->company()->value('require_approved_devices');
            $device = Device::query()->firstOrNew([
                'company_id' => $user->activeCompanyId(),
                'device_uuid' => $deviceUuid,
            ]);

            if ($device->exists && (int) $device->user_id !== (int) $user->getKey()) {
                throw new AuthorizationException('This device is already registered to another user.');
            }

            $device->fill([
                'user_id' => $user->getKey(),
                'name' => $name,
                'platform' => $platform,
                'fingerprint_hash' => $fingerprint ? hash('sha256', $fingerprint) : null,
                'metadata' => $metadata,
                'last_seen_at' => now(),
            ]);

            if (! $device->exists) {
                $device->status = $autoApprove ? DeviceStatus::Approved : DeviceStatus::Pending;
                $device->approved_at = $autoApprove ? now() : null;
            }

            $device->save();
            Activity::log('device_registered', $device, "Device {$device->name} registered");

            return $device;
        });
    }

    public function approve(Device $device, User $actor): Device
    {
        return $this->decide($device, $actor, DeviceStatus::Approved);
    }

    public function revoke(Device $device, User $actor): Device
    {
        return $this->decide($device, $actor, DeviceStatus::Revoked);
    }

    private function decide(Device $device, User $actor, DeviceStatus $status): Device
    {
        return DB::transaction(function () use ($device, $actor, $status): Device {
            throw_unless($actor->can('devices.approve'), new AuthorizationException(
                'You do not have permission to manage devices.',
            ));

            $device = Device::query()->lockForUpdate()->findOrFail($device->getKey());
            throw_unless($actor->hasCompanyAccess((int) $device->company_id), new AuthorizationException(
                'Cross-company device management is forbidden.',
            ));

            $approved = $status === DeviceStatus::Approved;
            $device->update([
                'status' => $status,
                'approved_by' => $approved ? $actor->getKey() : $device->approved_by,
                'approved_at' => $approved ? now() : $device->approved_at,
                'revoked_by' => $approved ? null : $actor->getKey(),
                'revoked_at' => $approved ? null : now(),
            ]);
            Activity::log(
                $approved ? 'device_approved' : 'device_revoked',
                $device,
                "Device {$device->name} {$status->value}",
            );

            return $device->fresh();
        });
    }
}
