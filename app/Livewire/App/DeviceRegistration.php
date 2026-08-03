<?php

namespace App\Livewire\App;

use App\Models\Device;
use App\Services\DeviceService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class DeviceRegistration extends Component
{
    public string $deviceUuid = '';

    public string $name = '';

    public string $platform = '';

    public string $fingerprint = '';

    public ?Device $device = null;

    public ?string $errorMessage = null;

    public function loadStatus(): void
    {
        if ($this->deviceUuid === '') {
            return;
        }

        $this->device = Device::query()
            ->where('user_id', auth()->id())
            ->where('device_uuid', $this->deviceUuid)
            ->first();
    }

    public function register(): void
    {
        $this->validate([
            'deviceUuid' => ['required', 'uuid'],
            'name' => ['required', 'string', 'max:255'],
            'platform' => ['nullable', 'string', 'max:255'],
            'fingerprint' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $this->device = app(DeviceService::class)->register(
                auth()->user(),
                $this->deviceUuid,
                $this->name,
                $this->platform ?: null,
                $this->fingerprint ?: null,
            );
            $this->dispatch(
                'device-registered',
                deviceUuid: $this->deviceUuid,
                approved: $this->device->status->value === 'approved',
            );
        } catch (\Throwable $exception) {
            report($exception);
            $this->errorMessage = __('app.device_registration_failed');
        }
    }

    public function render()
    {
        return view('livewire.app.device-registration');
    }
}
