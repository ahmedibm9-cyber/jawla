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
        <svg aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24" width="20" height="20" style="opacity:.7"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    </a>

    {{-- Quick Stats --}}
    <div class="home-stats" style="margin-top:-4px">
        <div class="home-stat-card">
            <div class="home-stat-number" style="color:var(--clr-accent)">{{ number_format($cashBoxBalance, 2) }}</div>
            <div class="home-stat-label">{{ __('app.cash_box_balance') }}</div>
        </div>
        <div class="home-stat-card">
            <div class="home-stat-number" style="color:var(--clr-accent-blue)">{{ $vanStockCount }}</div>
            <div class="home-stat-label">{{ __('app.van_stock_items') }}</div>
        </div>
    </div>

    {{-- Account --}}
    <div class="more-section">
        <h2 class="more-section-title">{{ __('app.account') }}</h2>

        <a href="/app/profile" class="more-item">
            <div class="more-item-icon more-icon-blue">
                <svg aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24" width="20" height="20"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            </div>
            <div class="more-item-body">
                <span class="more-item-label">{{ __('app.profile') }}</span>
                <span class="more-item-desc">{{ __('app.personal_info') }}</span>
            </div>
            <svg class="more-item-chevron" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </a>

        <a href="/app/settings" class="more-item">
            <div class="more-item-icon more-icon-blue">
                <svg aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24" width="20" height="20"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            </div>
            <div class="more-item-body">
                <span class="more-item-label">{{ __('app.settings') }}</span>
                <span class="more-item-desc">{{ __('app.language') }}</span>
            </div>
            <svg class="more-item-chevron" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </a>
    </div>

    {{-- Sales Actions --}}
    <div class="more-section">
        <h2 class="more-section-title">{{ __('app.sales') }}</h2>

        <a href="/app/sell" class="more-item">
            <div class="more-item-icon more-icon-green">
                <svg aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24" width="20" height="20"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
            </div>
            <div class="more-item-body">
                <span class="more-item-label">{{ __('app.create_invoice') }}</span>
                <span class="more-item-desc">{{ __('app.create_new_invoice') }}</span>
            </div>
            <svg class="more-item-chevron" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </a>

        <a href="/app/stock" class="more-item">
            <div class="more-item-icon more-icon-green">
                <svg aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24" width="20" height="20"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
            </div>
            <div class="more-item-body">
                <span class="more-item-label">{{ __('app.stock') }}</span>
                <span class="more-item-desc">{{ __('app.search_stock_report_shortages') }}</span>
            </div>
            <svg class="more-item-chevron" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </a>

        <a href="/app/transfers" class="more-item">
            <div class="more-item-icon more-icon-blue">
                <svg aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24" width="20" height="20"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
            </div>
            <div class="more-item-body">
                <span class="more-item-label">{{ __('app.van_transfers') }}</span>
                <span class="more-item-desc">{{ __('app.receive_van_stock') }}</span>
            </div>
            <svg class="more-item-chevron" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </a>

        <a href="/app/quotations" class="more-item">
            <div class="more-item-icon more-icon-blue">
                <svg aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24" width="20" height="20"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
            </div>
            <div class="more-item-body">
                <span class="more-item-label">{{ __('app.quotations') }}</span>
                <span class="more-item-desc">{{ __('app.price_quotations') }}</span>
            </div>
            <svg class="more-item-chevron" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </a>

        <a href="/app/returns" class="more-item">
            <div class="more-item-icon more-icon-amber">
                <svg aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24" width="20" height="20"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
            </div>
            <div class="more-item-body">
                <span class="more-item-label">{{ __('app.log_return') }}</span>
                <span class="more-item-desc">{{ __('app.log_product_return') }}</span>
            </div>
            <svg class="more-item-chevron" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </a>
    </div>

    {{-- Finance --}}
    <div class="more-section">
        <h2 class="more-section-title">{{ __('app.finance') }}</h2>

        <a href="/app/collect-payment" class="more-item">
            <div class="more-item-icon more-icon-emerald">
                <svg aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24" width="20" height="20"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            </div>
            <div class="more-item-body">
                <span class="more-item-label">{{ __('app.collect_payment') }}</span>
                <span class="more-item-desc">{{ __('app.collect_payment_from_customer') }}</span>
            </div>
            <svg class="more-item-chevron" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </a>

        <a href="/app/expenses" class="more-item">
            <div class="more-item-icon more-icon-red">
                <svg aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24" width="20" height="20"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div class="more-item-body">
                <span class="more-item-label">{{ __('app.log_expense') }}</span>
                <span class="more-item-desc">{{ __('app.log_an_expense') }}</span>
            </div>
            <svg class="more-item-chevron" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </a>

        <a href="/app/reconcile" class="more-item">
            <div class="more-item-icon more-icon-emerald">
                <svg aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24" width="20" height="20"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
            </div>
            <div class="more-item-body">
                <span class="more-item-label">{{ __('app.cash_reconciliation') }}</span>
                <span class="more-item-desc">{{ __('app.reconcile_your_cashbox') }}</span>
            </div>
            <svg class="more-item-chevron" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </a>
    </div>

    {{-- Other --}}
    <div class="more-section">
        <h2 class="more-section-title">{{ __('app.other') }}</h2>

        <a href="/app/customers/create" class="more-item">
            <div class="more-item-icon more-icon-purple">
                <svg aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24" width="20" height="20"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
            </div>
            <div class="more-item-body">
                <span class="more-item-label">{{ __('app.add_customer') }}</span>
                <span class="more-item-desc">{{ __('app.register_new_customer') }}</span>
            </div>
            <svg class="more-item-chevron" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </a>

        <a href="/app/complaints" class="more-item">
            <div class="more-item-icon more-icon-orange">
                <svg aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24" width="20" height="20"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </div>
            <div class="more-item-body">
                <span class="more-item-label">{{ __('app.log_complaint') }}</span>
                <span class="more-item-desc">{{ __('app.report_customer_complaint') }}</span>
            </div>
            <svg class="more-item-chevron" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </a>

        <a href="/app/purchase-offer" class="more-item">
            <div class="more-item-icon more-icon-teal">
                <svg aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24" width="20" height="20"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
            </div>
            <div class="more-item-body">
                <span class="more-item-label">{{ __('app.purchase_offer') }}</span>
                <span class="more-item-desc">{{ __('app.submit_purchase_offer') }}</span>
            </div>
            <svg class="more-item-chevron" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </a>
    </div>

    {{-- Logout --}}
    <div class="more-section">
        <form action="/app/logout" method="POST" aria-label="{{ __('app.logout') }}">
            @csrf
            <button type="submit" class="more-logout-btn">
                <svg aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24" width="20" height="20"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                {{ __('app.logout') }}
            </button>
        </form>
    </div>
</div>

<x-tab-bar active="more" />
</div>
