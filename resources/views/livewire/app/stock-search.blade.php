<div>
<div class="main-content p-4">
    <h2 class="m-0 mb-4">{{ __('app.stock') }}</h2>

    <div class="mb-4">
        <input type="text" wire:model.live.debounce.300ms="search" aria-label="{{ app()->getLocale() === 'ar' ? 'بحث' : 'Search' }}" autocomplete="off" class="w-full p-3 border border-border rounded-xl text-base"
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

<nav class="tab-bar" aria-label="Bottom navigation">
    <a href="/app" class="tab-item"><svg aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>{{ __('app.home') }}</a>
    <a href="/app/customers" class="tab-item"><svg aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>{{ __('app.customers') }}</a>
    <a href="/app/stock" class="tab-item active"><svg aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>{{ __('app.stock') }}</a>
    <a href="/app/more" class="tab-item"><svg aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>{{ __('app.more') }}</a>
</nav>
</div>