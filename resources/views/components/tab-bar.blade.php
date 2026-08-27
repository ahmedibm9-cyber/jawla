@props(['active' => 'home'])

<nav class="tab-bar" aria-label="Bottom navigation">
    <a href="/app" class="tab-bar-brand" aria-label="Jawla">
        <img src="/images/green-j.webp" alt="Jawla" class="logo-light" width="26" height="30">
        <img src="/images/white-j.webp" alt="Jawla" class="logo-dark" width="26" height="30">
    </a>
    <a href="/app" class="tab-item {{ $active === 'home' ? 'active' : '' }}" aria-label="{{ __('app.home') }}" @if($active === 'home') aria-current="page" @endif>
        <x-heroicon-o-home class="w-6 h-6" />
        <span>{{ __('app.home') }}</span>
    </a>
    <a href="/app/visits" class="tab-item {{ $active === 'visits' ? 'active' : '' }}" aria-label="{{ __('app.visits') }}" @if($active === 'visits') aria-current="page" @endif>
        <x-heroicon-o-map-pin class="w-6 h-6" />
        <span>{{ __('app.visits') }}</span>
    </a>
    <a href="/app/customers" class="tab-item {{ $active === 'customers' ? 'active' : '' }}" aria-label="{{ __('app.customers') }}" @if($active === 'customers') aria-current="page" @endif>
        <x-heroicon-o-users class="w-6 h-6" />
        <span>{{ __('app.customers') }}</span>
    </a>
    <a href="/app/orders" class="tab-item {{ $active === 'orders' ? 'active' : '' }}" aria-label="{{ __('app.orders') }}" @if($active === 'orders') aria-current="page" @endif>
        <x-heroicon-o-document-text class="w-6 h-6" />
        <span>{{ __('app.orders') }}</span>
    </a>
    <a href="/app/more" class="tab-item {{ $active === 'more' ? 'active' : '' }}" aria-label="{{ __('app.more') }}" @if($active === 'more') aria-current="page" @endif>
        <x-heroicon-o-bars-3 class="w-6 h-6" />
        <span>{{ __('app.more') }}</span>
    </a>
</nav>
