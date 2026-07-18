<div>
<div class="main-content p-4">
    <h2 class="m-0 mb-4">{{ app()->getLocale() === 'ar' ? 'تسجيل شكوى' : 'Log Complaint' }}</h2>

    @if($successMessage)
        <div class="toast toast-success relative top-0 mb-4" aria-live="polite" style="transform:none">{{ $successMessage }}</div>
    @endif

    <div class="card">
        <label for="customer_id" class="font-semibold block mb-1">{{ app()->getLocale() === 'ar' ? 'العميل' : 'Customer' }}</label>
        <select id="customer_id" wire:model="customer_id" class="w-full p-3 border border-border rounded-lg">
            <option value="">{{ app()->getLocale() === 'ar' ? 'اختر…' : 'Select…' }}</option>
            @foreach($customers as $c)
                <option value="{{ $c->id }}">{{ $c->name_ar }}</option>
            @endforeach
        </select>
        @error('customer_id') <small class="text-danger">{{ $message }}</small> @enderror
    </div>

    <div class="card">
        <label for="complaint_type" class="font-semibold block mb-1">{{ app()->getLocale() === 'ar' ? 'النوع' : 'Type' }}</label>
        <select id="complaint_type" wire:model="complaint_type" class="w-full p-3 border border-border rounded-lg">
            <option value="non_conforming_materials">{{ app()->getLocale() === 'ar' ? 'مواد غير مطابقة' : 'Non-conforming materials' }}</option>
            <option value="delivery_issue">{{ app()->getLocale() === 'ar' ? 'مشكلة تسليم' : 'Delivery issue' }}</option>
            <option value="quality_issue">{{ app()->getLocale() === 'ar' ? 'مشكلة جودة' : 'Quality issue' }}</option>
            <option value="pricing_issue">{{ app()->getLocale() === 'ar' ? 'مشكلة تسعير' : 'Pricing issue' }}</option>
            <option value="other">{{ app()->getLocale() === 'ar' ? 'أخرى' : 'Other' }}</option>
        </select>
    </div>

    <div class="card">
        <label for="description" class="font-semibold block mb-1">{{ app()->getLocale() === 'ar' ? 'الوصف' : 'Description' }}</label>
        <textarea id="description" wire:model="description" rows="3" autocomplete="off" class="w-full p-3 border border-border rounded-lg"></textarea>
        @error('description') <small class="text-danger">{{ $message }}</small> @enderror
    </div>

    <button class="btn btn-primary w-full" wire:click="submit" wire:loading.attr="disabled">
        <span wire:loading.remove>{{ app()->getLocale() === 'ar' ? 'إرسال' : 'Submit' }}</span>
        <span wire:loading>{{ app()->getLocale() === 'ar' ? 'جاري…' : 'Sending…' }}</span>
    </button>
</div>

<nav class="tab-bar" aria-label="Bottom navigation">
    <a href="/app" class="tab-item"><svg aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>{{ __('app.home') }}</a>
    <a href="/app/customers" class="tab-item"><svg aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>{{ __('app.customers') }}</a>
    <a href="/app/stock" class="tab-item"><svg aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>{{ __('app.stock') }}</a>
    <a href="/app/more" class="tab-item"><svg aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>{{ __('app.more') }}</a>
</nav>
</div>