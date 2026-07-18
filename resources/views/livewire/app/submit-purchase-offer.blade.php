<div>
<div class="main-content p-4">
    <h2 class="m-0 mb-4">{{ app()->getLocale() === 'ar' ? 'عرض شراء' : 'Purchase Offer' }}</h2>

    @if($successMessage)
        <div class="toast toast-success relative top-0 mb-4" aria-live="polite" style="transform:none">{{ $successMessage }}</div>
    @endif

    <div class="card">
        <label for="product_id" class="font-semibold block mb-1">{{ app()->getLocale() === 'ar' ? 'المنتج' : 'Product' }}</label>
        <select id="product_id" wire:model="product_id" class="w-full p-3 border border-border rounded-lg">
            <option value="">{{ app()->getLocale() === 'ar' ? 'اختر…' : 'Select…' }}</option>
            @foreach($products as $p)
                <option value="{{ $p->id }}">{{ $p->name_ar }}</option>
            @endforeach
        </select>
        @error('product_id') <small class="text-danger">{{ $message }}</small> @enderror
    </div>

    <div class="card">
        <label for="supplier_id" class="font-semibold block mb-1">{{ app()->getLocale() === 'ar' ? 'المورد (اختياري)' : 'Supplier (optional)' }}</label>
        <select id="supplier_id" wire:model="supplier_id" class="w-full p-3 border border-border rounded-lg">
            <option value="">{{ app()->getLocale() === 'ar' ? 'بدون' : 'N/A' }}</option>
            @foreach($suppliers as $s)
                <option value="{{ $s->id }}">{{ $s->name_ar ?? $s->name_en }}</option>
            @endforeach
        </select>
    </div>

    <div class="card flex gap-3">
        <div class="flex-1">
            <label for="quantity" class="font-semibold block mb-1">{{ app()->getLocale() === 'ar' ? 'الكمية' : 'Quantity' }}</label>
            <input type="number" step="0.001" id="quantity" name="quantity" wire:model="quantity" autocomplete="off" class="w-full p-3 border border-border rounded-lg">
            @error('quantity') <small class="text-danger">{{ $message }}</small> @enderror
        </div>
        <div class="flex-1">
            <label for="offered_price" class="font-semibold block mb-1">{{ app()->getLocale() === 'ar' ? 'السعر المعروض' : 'Offered Price' }}</label>
            <input type="number" step="0.01" id="offered_price" name="offered_price" wire:model="offered_price" autocomplete="off" class="w-full p-3 border border-border rounded-lg">
            @error('offered_price') <small class="text-danger">{{ $message }}</small> @enderror
        </div>
    </div>

    <div class="card">
        <label for="payment_terms" class="font-semibold block mb-1">{{ app()->getLocale() === 'ar' ? 'شروط الدفع' : 'Payment Terms' }}</label>
        <textarea id="payment_terms" wire:model="payment_terms" rows="2" autocomplete="off" class="w-full p-3 border border-border rounded-lg"></textarea>
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
    <a href="/app/more" class="tab-item active"><svg aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>{{ __('app.more') }}</a>
</nav>
</div>