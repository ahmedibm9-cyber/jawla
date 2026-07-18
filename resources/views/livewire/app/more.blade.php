<div>
<div class="main-content p-4">
    <h2 class="m-0 mb-4">{{ __('app.more') }}</h2>

    <div class="card flex items-center gap-3">
        <div class="size-12 rounded-full bg-accent text-white flex items-center justify-center font-bold text-lg">
            {{ strtoupper(substr($user->name, 0, 1)) }}
        </div>
        <div>
            <strong class="block">{{ $user->name }}</strong>
            <small class="text-text-secondary">{{ $user->email }}</small>
        </div>
    </div>

    <div class="mt-4">
        <a href="/app/customers/create" class="card block no-underline text-inherit">
            <div class="flex justify-between items-center">
                <span>{{ app()->getLocale() === 'ar' ? 'إضافة عميل' : 'Add Customer' }}</span>
                <span class="text-text-secondary">&rsaquo;</span>
            </div>
        </a>
        <a href="/app/quotations" class="card block no-underline text-inherit mt-2">
            <div class="flex justify-between items-center">
                <span>{{ __('app.quotations') }}</span>
                <span class="text-text-secondary">&rsaquo;</span>
            </div>
        </a>
        <a href="/app/complaints" class="card block no-underline text-inherit mt-2">
            <div class="flex justify-between items-center">
                <span>{{ app()->getLocale() === 'ar' ? 'تسجيل شكوى' : 'Log Complaint' }}</span>
                <span class="text-text-secondary">&rsaquo;</span>
            </div>
        </a>
        <a href="/app/purchase-offer" class="card block no-underline text-inherit mt-2">
            <div class="flex justify-between items-center">
                <span>{{ app()->getLocale() === 'ar' ? 'عرض شراء' : 'Purchase Offer' }}</span>
                <span class="text-text-secondary">&rsaquo;</span>
            </div>
        </a>
    </div>

    <form action="/app/logout" method="POST" class="mt-6">
        @csrf
        <button type="submit" class="btn btn-danger w-full">{{ __('app.logout') }}</button>
    </form>
</div>

<nav class="tab-bar" aria-label="Bottom navigation">
    <a href="/app" class="tab-item"><svg aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>{{ __('app.home') }}</a>
    <a href="/app/customers" class="tab-item"><svg aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>{{ __('app.customers') }}</a>
    <a href="/app/stock" class="tab-item"><svg aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>{{ __('app.stock') }}</a>
    <a href="/app/more" class="tab-item active"><svg aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>{{ __('app.more') }}</a>
</nav>
</div>