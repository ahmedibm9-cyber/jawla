<?php

namespace App\Http\Middleware;

use App\Enums\DeviceStatus;
use App\Models\Device;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class EnsureApprovedDevice
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // ponytail: defensive check — column may not exist on older migrations
        $columnExists = Schema::hasColumn('companies', 'require_approved_devices');

        $requiresApproval = $user !== null
            && $columnExists
            && (bool) $user->company()->value('require_approved_devices');

        if ($user === null || ! $requiresApproval || $request->routeIs('app.device', 'app.logout')) {
            return $next($request);
        }

        $rawDeviceUuid = (string) $request->cookie('jawla_device_id');

        if ($rawDeviceUuid === '') {
            return redirect()->route('app.device');
        }

        try {
            $deviceUuid = decrypt($rawDeviceUuid);
        } catch (\Throwable) {
            // ponytail: backward-compat — unencrypted cookie from before the fix
            $deviceUuid = $rawDeviceUuid;
        }

        $approved = $deviceUuid !== '' && Device::query()
            ->where('user_id', $user->getKey())
            ->where('device_uuid', $deviceUuid)
            ->where('status', DeviceStatus::Approved->value)
            ->where('last_seen_at', '>=', now()->subDays(90))
            ->exists();

        if (! $approved) {
            return redirect()->route('app.device');
        }

        Device::query()
            ->where('user_id', $user->getKey())
            ->where('device_uuid', $deviceUuid)
            ->where(fn ($query) => $query->whereNull('last_seen_at')->orWhere('last_seen_at', '<', now()->subMinutes(15)))
            ->update(['last_seen_at' => now()]);

        return $next($request);
    }
}
