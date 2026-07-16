<div>
<div class="main-content" style="padding:16px">
    <h2 style="margin:0 0 16px">{{ __('app.customers') }}</h2>

<div style="margin-bottom:16px">
        <input type="text" wire:model.live="search" aria-label="{{ app()->getLocale() === 'ar' ? 'بحث' : 'Search' }}" autocomplete="off" style="width:100%;padding:12px;border:1px solid #d1d5db;border-radius:10px;font-size:1rem"
            placeholder="{{ app()->getLocale() === 'ar' ? 'ابحث بالاسم أو الهاتف…' : 'Search customers…' }}">
    </div>

    <a href="/app/customers/create" class="btn btn-primary" style="display:block;text-decoration:none;margin-bottom:12px;text-align:center">
        {{ app()->getLocale() === 'ar' ? '+ إضافة عميل' : '+ Add Customer' }}
    </a>

    @forelse($customers as $customer)
<div class="card">
            <strong style="display:block">{{ $customer->name_ar }}</strong>
            <small style="color:#6b7280">{{ $customer->code }} · {{ $customer->phone }}</small>
            <p style="margin:4px 0 0;font-size:0.85rem;color:#9ca3af">{{ $customer->address }}</p>
            @if($customer->latitude && $customer->longitude)
                <a href="https://www.google.com/maps/dir/?api=1&destination={{ $customer->latitude }},{{ $customer->longitude }}"
                   target="_blank" class="maps-link">
                    <svg aria-hidden="true" width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-1.447-.894L15 9m-6-2l6-3m6 10V9m-6 10V9"/></svg>
                    {{ app()->getLocale() === 'ar' ? 'اتجاهات' : 'Directions' }}
                </a>
            @endif
        </div>
    @empty
        <div class="card" style="text-align:center;padding:32px;color:#9ca3af">
            {{ app()->getLocale() === 'ar' ? 'لا يوجد عملاء' : 'No customers found' }}
        </div>
    @endforelse
</div>

<nav class="tab-bar" aria-label="Bottom navigation">
    <a href="/app" class="tab-item">
        <svg aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
        {{ __('app.home') }}
    </a>
    <a href="/app/customers" class="tab-item active">
        <svg aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
        {{ __('app.customers') }}
    </a>
    <a href="/app/stock" class="tab-item">
        <svg aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
        {{ __('app.stock') }}
    </a>
    <a href="/app/more" class="tab-item">
        <svg aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
        {{ __('app.more') }}
    </a>
</nav>
</div>