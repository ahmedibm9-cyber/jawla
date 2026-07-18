<div>
<div class="main-content">
    <x-page-header
        :title="app()->getLocale() === 'ar' ? 'عرض شراء' : 'Purchase Offer'"
        :icon="'<svg fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\" width=\"22\" height=\"22\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z\"/></svg>'"
    />

    <div class="page-body">
        @if($successMessage)
            <div class="toast toast-success relative top-0 mb-4" aria-live="polite" style="transform:none">{{ $successMessage }}</div>
        @endif

        <form wire:submit="submit">
            <div class="form-group">
                <label for="product_id" class="form-label">{{ app()->getLocale() === 'ar' ? 'المنتج' : 'Product' }}</label>
                <select id="product_id" wire:model="product_id" class="form-select">
                    <option value="">{{ app()->getLocale() === 'ar' ? 'اختر…' : 'Select…' }}</option>
                    @foreach($products as $p)
                        <option value="{{ $p->id }}">{{ $p->name_ar }}</option>
                    @endforeach
                </select>
                @error('product_id') <small class="form-error">{{ $message }}</small> @enderror
            </div>

            <div class="form-group">
                <label for="supplier_id" class="form-label">{{ app()->getLocale() === 'ar' ? 'المورد (اختياري)' : 'Supplier (optional)' }}</label>
                <select id="supplier_id" wire:model="supplier_id" class="form-select">
                    <option value="">{{ app()->getLocale() === 'ar' ? 'بدون' : 'N/A' }}</option>
                    @foreach($suppliers as $s)
                        <option value="{{ $s->id }}">{{ $s->name_ar ?? $s->name_en }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="quantity" class="form-label">{{ app()->getLocale() === 'ar' ? 'الكمية' : 'Quantity' }}</label>
                    <input type="number" step="0.001" id="quantity" name="quantity" wire:model="quantity" autocomplete="off" class="form-input">
                    @error('quantity') <small class="form-error">{{ $message }}</small> @enderror
                </div>
                <div class="form-group">
                    <label for="offered_price" class="form-label">{{ app()->getLocale() === 'ar' ? 'السعر المعروض' : 'Offered Price' }}</label>
                    <input type="number" step="0.01" id="offered_price" name="offered_price" wire:model="offered_price" autocomplete="off" class="form-input">
                    @error('offered_price') <small class="form-error">{{ $message }}</small> @enderror
                </div>
            </div>

            <div class="form-group">
                <label for="payment_terms" class="form-label">{{ app()->getLocale() === 'ar' ? 'شروط الدفع' : 'Payment Terms' }}</label>
                <textarea id="payment_terms" wire:model="payment_terms" rows="2" autocomplete="off" class="form-textarea"></textarea>
            </div>

            <button class="btn btn-primary w-full" wire:click="submit" wire:loading.attr="disabled">
                <span wire:loading.remove>{{ app()->getLocale() === 'ar' ? 'إرسال' : 'Submit' }}</span>
                <span wire:loading>{{ app()->getLocale() === 'ar' ? 'جاري…' : 'Sending…' }}</span>
            </button>
        </form>
    </div>
</div>

<x-tab-bar active="more" />
</div>
