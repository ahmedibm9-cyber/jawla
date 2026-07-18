<div>
<div class="main-content">
    <x-page-header
        :title="__('app.stock')"
        :icon="'<svg fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\" width=\"22\" height=\"22\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4\"/></svg>'"
    />

    <div class="page-body">
        <div class="form-group">
            <input type="text" wire:model.live.debounce.300ms="search" aria-label="{{ app()->getLocale() === 'ar' ? 'بحث' : 'Search' }}" autocomplete="off" class="form-input"
                placeholder="{{ app()->getLocale() === 'ar' ? 'ابحث بالكود أو الاسم…' : 'Search by SKU or name…' }}">
        </div>

        @if(strlen($search) < 2)
            <div class="card text-center p-8 text-text-muted">
                <svg aria-hidden="true" class="size-10 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <p class="m-0">{{ app()->getLocale() === 'ar' ? 'ابحث عن منتج' : 'Search for a product' }}</p>
            </div>
        @elseif($results->isEmpty())
            <div class="card text-center p-6 text-text-muted">
                {{ app()->getLocale() === 'ar' ? 'لا نتائج' : 'No results' }}
            </div>
        @else
            @foreach($results as $product)
                <div class="card">
                    <div class="flex justify-between items-start">
                        <div>
                            <strong class="block">{{ $product->name_ar }}</strong>
                            <small class="text-text-secondary">{{ $product->sku }}</small>
                        </div>
                        <span class="font-bold text-accent">{{ number_format((float)$product->price, 2) }} EGP</span>
                    </div>
                    @if($product->stocks->isNotEmpty())
                        <div class="mt-2 pt-2 border-t border-surface-hover">
                            @foreach($product->stocks as $stock)
                                <div class="flex justify-between text-sm">
                                    <span class="text-text-secondary">{{ $stock->warehouse?->name_ar }}</span>
                                    <span class="font-semibold {{ $stock->quantity > 5 ? 'text-success' : 'text-warning' }}">{{ $stock->quantity }} {{ $product->unit }}</span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="mt-2 text-sm text-danger">{{ app()->getLocale() === 'ar' ? 'غير متوفر' : 'Out of stock' }}</p>
                    @endif
                </div>
            @endforeach
        @endif
    </div>
</div>

<x-tab-bar active="stock" />
</div>
