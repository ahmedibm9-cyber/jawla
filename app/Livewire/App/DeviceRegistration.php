<?php

namespace App\Livewire\App;

use App\Models\Device;
use App\Services\DeviceService;
use Illuminate\Support\Str;
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

    private ?string $encryptedDeviceCookie = null;

    public function mount(): void
    {
        $existing = request()->cookie('jawla_device_id');

        if ($existing !== null) {
            try {
                $this->deviceUuid = decrypt($existing);
            } catch (\Throwable) {
                $this->deviceUuid = $existing;
            }

            $this->loadStatus();

            return;
        }

        $this->deviceUuid = (string) Str::uuid();
        $this->encryptedDeviceCookie = encrypt($this->deviceUuid);
    }

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

            // Encrypt the device UUID before storing it in the cookie so it
            // cannot be trivially forged by the client.
            $this->encryptedDeviceCookie = encrypt($this->deviceUuid);
        } catch (\Throwable $exception) {
            report($exception);
            $this->errorMessage = __('app.device_registration_failed');
        }
    }

    public function render()
    {
        $view = view('livewire.app.device-registration');

        if ($this->encryptedDeviceCookie !== null) {
            return response()->view('livewire.app.device-registration', [
                'device' => $this->device,
                'errorMessage' => $this->errorMessage,
            ])->withCookie(cookie()->forever('jawla_device_id', $this->encryptedDeviceCookie, null, null, true, true, false, 'lax'));
        }

        return $view;
    }
}
