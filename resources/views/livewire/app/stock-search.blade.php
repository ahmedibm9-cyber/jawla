<div class="main-content" style="padding:16px">
    <h2 style="margin:0 0 16px">{{ __('app.stock') }}</h2>

    <div style="margin-bottom:16px">
        <input type="text" wire:model.live.debounce.300ms="search" style="width:100%;padding:12px;border:1px solid #d1d5db;border-radius:10px;font-size:1rem"
            placeholder="{{ app()->getLocale() === 'ar' ? 'ابحث بالكود أو الاسم...' : 'Search by SKU or name...' }}">
    </div>

    @if(strlen($search) < 2)
        <div class="card" style="text-align:center;padding:32px 16px;color:#9ca3af">
            <svg style="width:40px;height:40px;margin-bottom:12px" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <p style="margin:0">{{ app()->getLocale() === 'ar' ? 'ابحث عن منتج' : 'Search for a product' }}</p>
        </div>
    @elseif($results->isEmpty())
        <div class="card" style="text-align:center;padding:24px;color:#9ca3af">
            {{ app()->getLocale() === 'ar' ? 'لا نتائج' : 'No results' }}
        </div>
    @else
        @foreach($results as $product)
            <div class="card">
                <div style="display:flex;justify-content:space-between;align-items:start">
                    <div>
                        <strong style="display:block">{{ $product->name_ar }}</strong>
                        <small style="color:#6b7280">{{ $product->sku }}</small>
                    </div>
                    <span style="font-weight:700;color:#4DB848">{{ number_format((float)$product->price, 2) }} EGP</span>
                </div>
                @if($product->stocks->isNotEmpty())
                    <div style="margin-top:8px;padding-top:8px;border-top:1px solid #f3f4f6">
                        @foreach($product->stocks as $stock)
                            <div style="display:flex;justify-content:space-between;font-size:0.85rem">
                                <span style="color:#6b7280">{{ $stock->warehouse?->name_ar }}</span>
                                <span style="font-weight:600;color:{{ $stock->quantity > 5 ? '#16A34A' : '#D97706' }}">{{ $stock->quantity }} {{ $product->unit }}</span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p style="margin:8px 0 0;font-size:0.85rem;color:#DC2626">{{ app()->getLocale() === 'ar' ? 'غير متوفر' : 'Out of stock' }}</p>
                @endif
            </div>
        @endforeach
    @endif
</div>

<nav class="tab-bar">
    <a href="/app" class="tab-item"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>{{ __('app.home') }}</a>
    <a href="/app/customers" class="tab-item"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>{{ __('app.customers') }}</a>
    <a href="/app/stock" class="tab-item active"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>{{ __('app.stock') }}</a>
    <a href="/app/more" class="tab-item"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>{{ __('app.more') }}</a>
</nav>