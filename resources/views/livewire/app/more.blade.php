<div>
<div class="main-content more-page">
    {{-- Profile Hero --}}
    <a href="/app/profile" class="profile-hero" aria-label="{{ __('app.profile') }}">
        <div class="profile-hero-content">
            <div class="profile-hero-avatar">
                {{ strtoupper(substr($user->name, 0, 1)) }}
            </div>
            <div class="profile-hero-info">
                <p class="profile-hero-name">{{ $user->name }}</p>
                <p class="profile-hero-detail">{{ $user->employee_code ?? $user->email }}</p>
            </div>
        </div>
        <x-heroicon-o-chevron-right class="profile-hero-chevron opacity-70" />
    </a>

    {{-- Quick Stats --}}
    <div class="home-stats" style="margin-top:-4px">
        <div class="home-stat-card">
            <div class="home-stat-number text-accent">{{ number_format($cashBoxBalance, 2) }}</div>
            <div class="home-stat-label">{{ __('app.cash_box_balance') }}</div>
        </div>
        <div class="home-stat-card">
            <div class="home-stat-number text-accent-blue">{{ $vanStockCount }}</div>
            <div class="home-stat-label">{{ __('app.van_stock_items') }}</div>
        </div>
    </div>

    {{-- All Actions Grid --}}
    <div class="more-section">
        <h2 class="more-section-title">{{ __('app.account') }}</h2>
        <div class="more-grid">
            <a href="/app/profile" class="more-tile">
                <div class="more-tile-icon more-icon-blue">
                    <x-heroicon-o-user />
                </div>
                <span class="more-tile-label">{{ __('app.profile') }}</span>
            </a>
            <a href="/app/settings" class="more-tile">
                <div class="more-tile-icon more-icon-blue">
                    <x-heroicon-o-cog-6-tooth />
                </div>
                <span class="more-tile-label">{{ __('app.settings') }}</span>
            </a>
        </div>
    </div>

    <div class="more-section">
        <h2 class="more-section-title">{{ __('app.sales') }}</h2>
        <div class="more-grid">
            <a href="/app/sell" class="more-tile">
                <div class="more-tile-icon more-icon-green">
                    <x-heroicon-o-clipboard-document-check />
                </div>
                <span class="more-tile-label">{{ __('app.create_invoice') }}</span>
            </a>
            <a href="/app/collect-payment" class="more-tile">
                <div class="more-tile-icon more-icon-emerald">
                    <x-heroicon-o-banknotes />
                </div>
                <span class="more-tile-label">{{ __('app.collect_payment') }}</span>
            </a>
            <a href="/app/stock" class="more-tile">
                <div class="more-tile-icon more-icon-green">
                    <x-heroicon-o-cube />
                </div>
                <span class="more-tile-label">{{ __('app.stock') }}</span>
            </a>
            <a href="/app/transfers" class="more-tile">
                <div class="more-tile-icon more-icon-blue">
                    <x-heroicon-o-arrows-right-left />
                </div>
                <span class="more-tile-label">{{ __('app.van_transfers') }}</span>
            </a>
            <a href="/app/quotations" class="more-tile">
                <div class="more-tile-icon more-icon-blue">
                    <x-heroicon-o-document-text />
                </div>
                <span class="more-tile-label">{{ __('app.quotations') }}</span>
            </a>
            <a href="/app/returns" class="more-tile">
                <div class="more-tile-icon more-icon-amber">
                    <x-heroicon-o-arrow-uturn-left />
                </div>
                <span class="more-tile-label">{{ __('app.log_return') }}</span>
            </a>
        </div>
    </div>

    <div class="more-section">
        <h2 class="more-section-title">{{ __('app.finance') }}</h2>
        <div class="more-grid">
            <a href="/app/collect-payment" class="more-tile">
                <div class="more-tile-icon more-icon-emerald">
                    <x-heroicon-o-banknotes />
                </div>
                <span class="more-tile-label">{{ __('app.collect_payment') }}</span>
            </a>
            <a href="/app/expenses" class="more-tile">
                <div class="more-tile-icon more-icon-red">
                    <x-heroicon-o-currency-dollar />
                </div>
                <span class="more-tile-label">{{ __('app.log_expense') }}</span>
            </a>
            <a href="/app/reconcile" class="more-tile">
                <div class="more-tile-icon more-icon-emerald">
                    <x-heroicon-o-clipboard-document-list />
                </div>
                <span class="more-tile-label">{{ __('app.cash_reconciliation') }}</span>
            </a>
        </div>
    </div>

    <div class="more-section">
        <h2 class="more-section-title">{{ __('app.other') }}</h2>
        <div class="more-grid">
            <a href="/app/customers/create" class="more-tile">
                <div class="more-tile-icon more-icon-purple">
                    <x-heroicon-o-user-plus />
                </div>
                <span class="more-tile-label">{{ __('app.add_customer') }}</span>
            </a>
            <a href="/app/complaints" class="more-tile">
                <div class="more-tile-icon more-icon-orange">
                    <x-heroicon-o-exclamation-triangle />
                </div>
                <span class="more-tile-label">{{ __('app.log_complaint') }}</span>
            </a>
            <a href="/app/purchase-offer" class="more-tile">
                <div class="more-tile-icon more-icon-teal">
                    <x-heroicon-o-shopping-bag />
                </div>
                <span class="more-tile-label">{{ __('app.purchase_offer') }}</span>
            </a>
        </div>
    </div>

    {{-- Logout --}}
    <div class="more-section">
        <form action="/app/logout" method="POST" aria-label="{{ __('app.logout') }}" data-jawla-logout>
            @csrf
            <button type="submit" class="more-logout-btn">
                <x-heroicon-o-arrow-right-on-rectangle />
                {{ __('app.logout') }}
            </button>
        </form>
    </div>
</div>

<x-tab-bar active="more" />
</div>
