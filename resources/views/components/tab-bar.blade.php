@props(['active' => 'home'])

<nav class="tab-bar" aria-label="Bottom navigation">
    <a href="/app" class="tab-item {{ $active === 'home' ? 'active' : '' }}" aria-label="{{ __('app.home') }}" @if($active === 'home') aria-current="page" @endif>
        <svg aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
        {{ __('app.home') }}
    </a>
    <a href="/app/visits" class="tab-item {{ $active === 'visits' ? 'active' : '' }}" aria-label="{{ __('app.visits') }}" @if($active === 'visits') aria-current="page" @endif>
        <svg aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
        {{ __('app.visits') }}
    </a>
    <a href="/app/customers" class="tab-item {{ $active === 'customers' ? 'active' : '' }}" aria-label="{{ __('app.customers') }}" @if($active === 'customers') aria-current="page" @endif>
        <svg aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
        {{ __('app.customers') }}
    </a>
    <a href="/app/orders" class="tab-item {{ $active === 'orders' ? 'active' : '' }}" aria-label="{{ __('app.orders') }}" @if($active === 'orders') aria-current="page" @endif>
        <svg aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        {{ __('app.orders') }}
    </a>
    <a href="/app/more" class="tab-item {{ $active === 'more' ? 'active' : '' }}" aria-label="{{ __('app.more') }}" @if($active === 'more') aria-current="page" @endif>
        <svg aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
        {{ __('app.more') }}
    </a>
</nav>
