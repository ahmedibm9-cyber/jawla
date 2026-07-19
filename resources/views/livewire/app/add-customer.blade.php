<div>
<div class="main-content"
     x-data="{
        gpsDenied: false,
        getPosition() {
            if (!('geolocation' in navigator)) { this.gpsDenied = true; return; }
            this.gpsDenied = false;
            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    $wire.set('latitude', pos.coords.latitude);
                    $wire.set('longitude', pos.coords.longitude);
                },
                () => { this.gpsDenied = true; }
            );
        }
     }"
     x-init="getPosition()">
    <x-page-header :title="__('app.add_customer')">
        <x-slot:icon><svg fill='none' stroke='currentColor' viewBox='0 0 24 24' width='22' height='22'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z'/></svg></x-slot:icon>
    </x-page-header>

    <div class="page-body">
        <div x-show="gpsDenied" x-cloak class="card bg-amber-50 text-amber-800 mb-4" role="alert">
            {{ __('app.could_not_get_location') }}
            <button type="button" class="btn btn-outline text-sm mt-2 w-full" x-on:click="getPosition()">{{ __('app.retry') }}</button>
        </div>
        @if($successMessage)
            <div class="toast toast-success relative top-0 mb-4" aria-live="polite" style="transform:none">{{ $successMessage }}</div>
        @endif

        <form wire:submit="submit">
            <div class="form-group">
                <label for="name_ar" class="form-label">{{ __('app.name_ar') }}</label>
                <input type="text" id="name_ar" name="name_ar" wire:model="name_ar" autocomplete="off" class="form-input">
                @error('name_ar') <small class="form-error">{{ $message }}</small> @enderror
            </div>

            <div class="form-group">
                <label for="name_en" class="form-label">{{ __('app.name_en') }}</label>
                <input type="text" id="name_en" name="name_en" wire:model="name_en" autocomplete="off" class="form-input">
                @error('name_en') <small class="form-error">{{ $message }}</small> @enderror
            </div>

            <div class="form-group">
                <label for="phone" class="form-label">{{ __('app.phone') }}</label>
                <input type="tel" id="phone" name="phone" wire:model="phone" autocomplete="tel" inputmode="tel" class="form-input">
                @error('phone') <small class="form-error">{{ $message }}</small> @enderror
            </div>

            <div class="form-group">
                <label for="address" class="form-label">{{ __('app.address') }}</label>
                <textarea id="address" name="address" wire:model="address" rows="2" autocomplete="off" class="form-textarea"></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="latitude" class="form-label">{{ __('app.latitude') }}</label>
                    <input type="number" step="0.0000001" id="latitude" name="latitude" wire:model="latitude" autocomplete="off" class="form-input">
                </div>
                <div class="form-group">
                    <label for="longitude" class="form-label">{{ __('app.longitude') }}</label>
                    <input type="number" step="0.0000001" id="longitude" name="longitude" wire:model="longitude" autocomplete="off" class="form-input">
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-full" wire:loading.attr="disabled">
                <span wire:loading.remove>{{ __('app.submit') }}</span>
                <span wire:loading>{{ __('app.sending') }}</span>
            </button>
        </form>
    </div>
</div>

<x-tab-bar active="customers" />
</div>
