<div>
<div class="main-content" style="padding:16px">
    <h2 style="margin:0 0 16px">{{ __('app.more') }}</h2>

    <div class="card" style="display:flex;align-items:center;gap:12px">
        <div style="width:48px;height:48px;border-radius:50%;background:#4DB848;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:1.2rem">
            {{ strtoupper(substr($user->name, 0, 1)) }}
        </div>
        <div>
            <strong style="display:block">{{ $user->name }}</strong>
            <small style="color:#6b7280">{{ $user->email }}</small>
        </div>
    </div>

    <div style="margin-top:16px">
        <a href="/app/customers/create" class="card" style="display:block;text-decoration:none;color:inherit">
            <div style="display:flex;justify-content:space-between;align-items:center">
                <span>{{ app()->getLocale() === 'ar' ? 'إضافة عميل' : 'Add Customer' }}</span>
                <span style="color:#6b7280">&rsaquo;</span>
            </div>
        </a>
        <a href="/app/quotations" class="card" style="display:block;text-decoration:none;color:inherit;margin-top:8px">
            <div style="display:flex;justify-content:space-between;align-items:center">
                <span>{{ __('app.quotations') }}</span>
                <span style="color:#6b7280">&rsaquo;</span>
            </div>
        </a>
        <a href="/app/complaints" class="card" style="display:block;text-decoration:none;color:inherit;margin-top:8px">
            <div style="display:flex;justify-content:space-between;align-items:center">
                <span>{{ app()->getLocale() === 'ar' ? 'تسجيل شكوى' : 'Log Complaint' }}</span>
                <span style="color:#6b7280">&rsaquo;</span>
            </div>
        </a>
        <a href="/app/purchase-offer" class="card" style="display:block;text-decoration:none;color:inherit;margin-top:8px">
            <div style="display:flex;justify-content:space-between;align-items:center">
                <span>{{ app()->getLocale() === 'ar' ? 'عرض شراء' : 'Purchase Offer' }}</span>
                <span style="color:#6b7280">&rsaquo;</span>
            </div>
        </a>
    </div>

    <form action="/app/logout" method="POST" style="margin-top:24px">
        @csrf
        <button type="submit" class="btn btn-danger" style="width:100%">{{ __('app.logout') }}</button>
    </form>
</div>

<nav class="tab-bar" aria-label="Bottom navigation">
    <a href="/app" class="tab-item"><svg aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>{{ __('app.home') }}</a>
    <a href="/app/customers" class="tab-item"><svg aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>{{ __('app.customers') }}</a>
    <a href="/app/stock" class="tab-item"><svg aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>{{ __('app.stock') }}</a>
    <a href="/app/more" class="tab-item active"><svg aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>{{ __('app.more') }}</a>
</nav>
</div>