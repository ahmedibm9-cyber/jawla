<div class="main-content" style="padding:16px"
     x-data="{
        getPosition() {
            if ('geolocation' in navigator) {
                navigator.geolocation.getCurrentPosition(
                    (pos) => {
                        $wire.set('latitude', pos.coords.latitude);
                        $wire.set('longitude', pos.coords.longitude);
                    },
                    () => {}
                );
            }
        }
     }"
     x-init="getPosition()">
    <h2 style="margin:0 0 16px">{{ app()->getLocale() === 'ar' ? 'إضافة عميل' : 'Add Customer' }}</h2>

    @if($successMessage)
        <div class="toast toast-success" aria-live="polite" style="position:relative;top:0;transform:none;margin-bottom:16px">{{ $successMessage }}</div>
    @endif

    <div class="card">
        <label for="name_ar" style="font-weight:600;display:block;margin-bottom:4px">{{ app()->getLocale() === 'ar' ? 'الاسم (عربي)' : 'Name (Arabic)' }}</label>
        <input type="text" id="name_ar" name="name_ar" wire:model="name_ar" autocomplete="off" style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:8px">
        @error('name_ar') <small style="color:#DC2626">{{ $message }}</small> @enderror
    </div>

    <div class="card">
        <label for="name_en" style="font-weight:600;display:block;margin-bottom:4px">{{ app()->getLocale() === 'ar' ? 'الاسم (إنجليزي)' : 'Name (English)' }}</label>
        <input type="text" id="name_en" name="name_en" wire:model="name_en" autocomplete="off" style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:8px">
        @error('name_en') <small style="color:#DC2626">{{ $message }}</small> @enderror
    </div>

    <div class="card">
        <label for="phone" style="font-weight:600;display:block;margin-bottom:4px">{{ app()->getLocale() === 'ar' ? 'الهاتف' : 'Phone' }}</label>
        <input type="tel" id="phone" name="phone" wire:model="phone" autocomplete="tel" inputmode="tel" style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:8px">
        @error('phone') <small style="color:#DC2626">{{ $message }}</small> @enderror
    </div>

    <div class="card">
        <label for="address" style="font-weight:600;display:block;margin-bottom:4px">{{ app()->getLocale() === 'ar' ? 'العنوان' : 'Address' }}</label>
        <textarea id="address" name="address" wire:model="address" rows="2" autocomplete="off" style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:8px"></textarea>
    </div>

    <div class="card" style="display:flex;gap:12px">
        <div style="flex:1">
            <label for="latitude" style="font-weight:600;display:block;margin-bottom:4px">{{ app()->getLocale() === 'ar' ? 'خط العرض' : 'Latitude' }}</label>
            <input type="number" step="0.0000001" id="latitude" name="latitude" wire:model="latitude" autocomplete="off" style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:8px">
        </div>
        <div style="flex:1">
            <label for="longitude" style="font-weight:600;display:block;margin-bottom:4px">{{ app()->getLocale() === 'ar' ? 'خط الطول' : 'Longitude' }}</label>
            <input type="number" step="0.0000001" id="longitude" name="longitude" wire:model="longitude" autocomplete="off" style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:8px">
        </div>
    </div>

    <button class="btn btn-primary" style="width:100%" wire:click="submit" wire:loading.attr="disabled">
        <span wire:loading.remove>{{ app()->getLocale() === 'ar' ? 'إرسال' : 'Submit' }}</span>
        <span wire:loading>{{ app()->getLocale() === 'ar' ? 'جاري…' : 'Sending…' }}</span>
    </button>
</div>

<nav class="tab-bar" aria-label="Bottom navigation">
    <a href="/app" class="tab-item"><svg aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>{{ __('app.home') }}</a>
    <a href="/app/customers" class="tab-item active"><svg aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>{{ __('app.customers') }}</a>
    <a href="/app/stock" class="tab-item"><svg aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>{{ __('app.stock') }}</a>
    <a href="/app/more" class="tab-item"><svg aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>{{ __('app.more') }}</a>
</nav>