<div class="main-content"
     x-data="{
        initialize() {
            let id = localStorage.getItem('jawla_device_id');
            if (!id) {
                id = crypto.randomUUID();
                localStorage.setItem('jawla_device_id', id);
            }
            $wire.set('deviceUuid', id);
            $wire.set('platform', navigator.userAgentData?.platform || navigator.platform || 'Web');
            $wire.set('name', `${navigator.userAgentData?.platform || navigator.platform || 'Web'} device`);
            $wire.set('fingerprint', `${navigator.userAgent}|${navigator.language}|${screen.width}x${screen.height}`);
            document.cookie = `jawla_device_id=${id}; Path=/; SameSite=Lax${location.protocol === 'https:' ? '; Secure' : ''}`;
            $wire.loadStatus();
        }
     }"
     x-init="initialize()"
     x-on:device-registered.window="if ($event.detail.approved) window.location.href = '/app'">
    <x-page-header :title="__('app.device_registration')">
        <x-slot:icon><x-heroicon-o-device-phone-mobile class="w-6 h-6" /></x-slot:icon>
    </x-page-header>

    <div class="page-body">
        @if($errorMessage)
            <x-ds.toast type="error" :message="$errorMessage" />
        @endif

        <div class="card">
            @if($device?->status->value === 'pending')
                <div class="text-center py-4">
                    <x-heroicon-o-clock class="w-12 h-12 mx-auto text-warning" />
                    <h2 class="font-semibold text-lg mt-3">{{ __('app.device_pending_title') }}</h2>
                    <p class="text-sm text-text-secondary mt-2">{{ __('app.device_pending_message') }}</p>
                </div>
            @elseif($device?->status->value === 'revoked')
                <div class="text-center py-4">
                    <x-heroicon-o-no-symbol class="w-12 h-12 mx-auto text-danger" />
                    <h2 class="font-semibold text-lg mt-3">{{ __('app.device_revoked_title') }}</h2>
                    <p class="text-sm text-text-secondary mt-2">{{ __('app.device_revoked_message') }}</p>
                </div>
            @else
                <h2 class="font-semibold text-lg">{{ __('app.device_register_title') }}</h2>
                <p class="text-sm text-text-secondary mt-2">{{ __('app.device_register_message') }}</p>

                <label class="block mt-4 text-sm font-medium">
                    {{ __('app.device_name') }}
                    <input type="text" wire:model="name" class="form-input mt-1" maxlength="255" required>
                </label>

                <button type="button" wire:click="register" wire:loading.attr="disabled" class="btn btn-primary w-full mt-4">
                    <span wire:loading.remove>{{ __('app.device_register') }}</span>
                    <span wire:loading>{{ __('app.saving') }}&hellip;</span>
                </button>
            @endif
        </div>

        <form action="/app/logout" method="POST" class="mt-4" data-jawla-logout>
            @csrf
            <button type="submit" class="btn btn-secondary w-full">{{ __('app.logout') }}</button>
        </form>
    </div>
</div>
