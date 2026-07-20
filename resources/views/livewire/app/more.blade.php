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
        <x-heroicon-o-chevron-right class="opacity-70" />
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

    {{-- Account --}}
    <div class="more-section">
        <h2 class="more-section-title">{{ __('app.account') }}</h2>

        <a href="/app/profile" class="more-item">
            <div class="more-item-icon more-icon-blue">
                <x-heroicon-o-user />
            </div>
            <div class="more-item-body">
                <span class="more-item-label">{{ __('app.profile') }}</span>
                <span class="more-item-desc">{{ __('app.personal_info') }}</span>
            </div>
            <x-heroicon-o-chevron-right class="more-item-chevron" />
        </a>

        <a href="/app/settings" class="more-item">
            <div class="more-item-icon more-icon-blue">
                <x-heroicon-o-cog-6-tooth />
            </div>
            <div class="more-item-body">
                <span class="more-item-label">{{ __('app.settings') }}</span>
                <span class="more-item-desc">{{ __('app.language') }}</span>
            </div>
            <x-heroicon-o-chevron-right class="more-item-chevron" />
        </a>
    </div>

    {{-- Sales Actions --}}
    <div class="more-section">
        <h2 class="more-section-title">{{ __('app.sales') }}</h2>

        <a href="/app/sell" class="more-item">
            <div class="more-item-icon more-icon-green">
                <x-heroicon-o-clipboard-document-check />
            </div>
            <div class="more-item-body">
                <span class="more-item-label">{{ __('app.create_invoice') }}</span>
                <span class="more-item-desc">{{ __('app.create_new_invoice') }}</span>
            </div>
            <x-heroicon-o-chevron-right class="more-item-chevron" />
        </a>

        <a href="/app/stock" class="more-item">
            <div class="more-item-icon more-icon-green">
                <x-heroicon-o-cube />
            </div>
            <div class="more-item-body">
                <span class="more-item-label">{{ __('app.stock') }}</span>
                <span class="more-item-desc">{{ __('app.search_stock_report_shortages') }}</span>
            </div>
            <x-heroicon-o-chevron-right class="more-item-chevron" />
        </a>

        <a href="/app/transfers" class="more-item">
            <div class="more-item-icon more-icon-blue">
                <x-heroicon-o-arrows-right-left />
            </div>
            <div class="more-item-body">
                <span class="more-item-label">{{ __('app.van_transfers') }}</span>
                <span class="more-item-desc">{{ __('app.receive_van_stock') }}</span>
            </div>
            <x-heroicon-o-chevron-right class="more-item-chevron" />
        </a>

        <a href="/app/quotations" class="more-item">
            <div class="more-item-icon more-icon-blue">
                <x-heroicon-o-document-text />
            </div>
            <div class="more-item-body">
                <span class="more-item-label">{{ __('app.quotations') }}</span>
                <span class="more-item-desc">{{ __('app.price_quotations') }}</span>
            </div>
            <x-heroicon-o-chevron-right class="more-item-chevron" />
        </a>

        <a href="/app/returns" class="more-item">
            <div class="more-item-icon more-icon-amber">
                <x-heroicon-o-arrow-uturn-left />
            </div>
            <div class="more-item-body">
                <span class="more-item-label">{{ __('app.log_return') }}</span>
                <span class="more-item-desc">{{ __('app.log_product_return') }}</span>
            </div>
            <x-heroicon-o-chevron-right class="more-item-chevron" />
        </a>
    </div>

    {{-- Finance --}}
    <div class="more-section">
        <h2 class="more-section-title">{{ __('app.finance') }}</h2>

        <a href="/app/collect-payment" class="more-item">
            <div class="more-item-icon more-icon-emerald">
                <x-heroicon-o-banknotes />
            </div>
            <div class="more-item-body">
                <span class="more-item-label">{{ __('app.collect_payment') }}</span>
                <span class="more-item-desc">{{ __('app.collect_payment_from_customer') }}</span>
            </div>
            <x-heroicon-o-chevron-right class="more-item-chevron" />
        </a>

        <a href="/app/expenses" class="more-item">
            <div class="more-item-icon more-icon-red">
                <x-heroicon-o-currency-dollar />
            </div>
            <div class="more-item-body">
                <span class="more-item-label">{{ __('app.log_expense') }}</span>
                <span class="more-item-desc">{{ __('app.log_an_expense') }}</span>
            </div>
            <x-heroicon-o-chevron-right class="more-item-chevron" />
        </a>

        <a href="/app/reconcile" class="more-item">
            <div class="more-item-icon more-icon-emerald">
                <x-heroicon-o-clipboard-document-list />
            </div>
            <div class="more-item-body">
                <span class="more-item-label">{{ __('app.cash_reconciliation') }}</span>
                <span class="more-item-desc">{{ __('app.reconcile_your_cashbox') }}</span>
            </div>
            <x-heroicon-o-chevron-right class="more-item-chevron" />
        </a>
    </div>

    {{-- Other --}}
    <div class="more-section">
        <h2 class="more-section-title">{{ __('app.other') }}</h2>

        <a href="/app/customers/create" class="more-item">
            <div class="more-item-icon more-icon-purple">
                <x-heroicon-o-user-plus />
            </div>
            <div class="more-item-body">
                <span class="more-item-label">{{ __('app.add_customer') }}</span>
                <span class="more-item-desc">{{ __('app.register_new_customer') }}</span>
            </div>
            <x-heroicon-o-chevron-right class="more-item-chevron" />
        </a>

        <a href="/app/complaints" class="more-item">
            <div class="more-item-icon more-icon-orange">
                <x-heroicon-o-exclamation-triangle />
            </div>
            <div class="more-item-body">
                <span class="more-item-label">{{ __('app.log_complaint') }}</span>
                <span class="more-item-desc">{{ __('app.report_customer_complaint') }}</span>
            </div>
            <x-heroicon-o-chevron-right class="more-item-chevron" />
        </a>

        <a href="/app/purchase-offer" class="more-item">
            <div class="more-item-icon more-icon-teal">
                <x-heroicon-o-shopping-bag />
            </div>
            <div class="more-item-body">
                <span class="more-item-label">{{ __('app.purchase_offer') }}</span>
                <span class="more-item-desc">{{ __('app.submit_purchase_offer') }}</span>
            </div>
            <x-heroicon-o-chevron-right class="more-item-chevron" />
        </a>
    </div>

    {{-- Logout --}}
    <div class="more-section">
        <form action="/app/logout" method="POST" aria-label="{{ __('app.logout') }}">
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
