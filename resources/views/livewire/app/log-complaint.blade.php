<div>
<div class="main-content">
    <x-page-header
        :title="app()->getLocale() === 'ar' ? 'تسجيل شكوى' : 'Log Complaint'"
        :icon="'<svg fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\" width=\"22\" height=\"22\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z\"/></svg>'"
    />

    <div class="page-body">
        @if($successMessage)
            <div class="toast toast-success relative top-0 mb-4" aria-live="polite" style="transform:none">{{ $successMessage }}</div>
        @endif

        <form wire:submit="submit">
            <div class="form-group">
                <label for="customer_id" class="form-label">{{ app()->getLocale() === 'ar' ? 'العميل' : 'Customer' }}</label>
                <select id="customer_id" wire:model="customer_id" class="form-select">
                    <option value="">{{ app()->getLocale() === 'ar' ? 'اختر…' : 'Select…' }}</option>
                    @foreach($customers as $c)
                        <option value="{{ $c->id }}">{{ $c->name_ar }}</option>
                    @endforeach
                </select>
                @error('customer_id') <small class="form-error">{{ $message }}</small> @enderror
            </div>

            <div class="form-group">
                <label for="complaint_type" class="form-label">{{ app()->getLocale() === 'ar' ? 'النوع' : 'Type' }}</label>
                <select id="complaint_type" wire:model="complaint_type" class="form-select">
                    <option value="non_conforming_materials">{{ app()->getLocale() === 'ar' ? 'مواد غير مطابقة' : 'Non-conforming materials' }}</option>
                    <option value="delivery_issue">{{ app()->getLocale() === 'ar' ? 'مشكلة تسليم' : 'Delivery issue' }}</option>
                    <option value="quality_issue">{{ app()->getLocale() === 'ar' ? 'مشكلة جودة' : 'Quality issue' }}</option>
                    <option value="pricing_issue">{{ app()->getLocale() === 'ar' ? 'مشكلة تسعير' : 'Pricing issue' }}</option>
                    <option value="other">{{ app()->getLocale() === 'ar' ? 'أخرى' : 'Other' }}</option>
                </select>
            </div>

            <div class="form-group">
                <label for="description" class="form-label">{{ app()->getLocale() === 'ar' ? 'الوصف' : 'Description' }}</label>
                <textarea id="description" wire:model="description" rows="3" autocomplete="off" class="form-textarea"></textarea>
                @error('description') <small class="form-error">{{ $message }}</small> @enderror
            </div>

            <button class="btn btn-primary w-full" wire:click="submit" wire:loading.attr="disabled">
                <span wire:loading.remove>{{ app()->getLocale() === 'ar' ? 'إرسال' : 'Submit' }}</span>
                <span wire:loading>{{ app()->getLocale() === 'ar' ? 'جاري…' : 'Sending…' }}</span>
            </button>
        </form>
    </div>
</div>

<x-tab-bar active="home" />
</div>
