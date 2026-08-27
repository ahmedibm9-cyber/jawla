<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}" data-theme="{{ session('theme', 'light') }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <meta name="theme-color" content="#4A8C2A">
  <link rel="icon" href="/images/logo-app-icon.webp" type="image/webp">
  <link rel="icon" href="/icons/icon-192.png" type="image/png">
  <link rel="apple-touch-icon" href="/icons/icon-192.png">
  <link rel="manifest" href="/manifest.json">
  <meta name="mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
  <link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Arabic:wght@400;500;600;700&display=swap" rel="stylesheet">
  @php
    $pageKeyMap = [
        'home' => 'home', 'visits' => 'visits', 'visit' => 'visits',
        'customers' => 'customers', 'customers.create' => 'customers',
        'orders' => 'orders', 'stock' => 'stock', 'more' => 'more',
        'notifications' => 'notifications', 'quotations' => 'quotations',
        'sell' => 'create_invoice', 'sell.customer' => 'create_invoice',
        'collect-payment' => 'collect_payment', 'returns' => 'log_return',
        'expenses' => 'log_expense', 'profile' => 'profile', 'settings' => 'settings',
    ];
    $pageKey = \Illuminate\Support\Str::after(request()->route()?->getName() ?? '', 'app.');
  @endphp
  <title>{{ isset($pageKeyMap[$pageKey]) ? __('app.'.$pageKeyMap[$pageKey]).' | Jawla' : 'Jawla' }}</title>
  <meta name="description" content="{{ l('تطبيق إدارة المبيعات الميدانية - Jawla', 'Field Sales Management PWA - Jawla') }}">
  @if(config('sentry.dsn'))
  <meta name="sentry-dsn" content="{{ config('sentry.dsn') }}">
  <meta name="sentry-environment" content="{{ config('sentry.environment', config('app.env', 'production')) }}">
  @endif
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <meta name="robots" content="noindex, nofollow">
  @auth
  <meta name="jawla-offline-identity" content="{{ hash_hmac('sha256', (string) auth()->id(), (string) config('app.key')) }}">
  @endauth
  @filamentStyles
  @vite(['resources/css/app.css', 'resources/js/app.js'])
  <style>:root{-webkit-tap-highlight-color:transparent}[x-cloak]{display:none!important}</style>
  <script>
    // Initialize dark mode before paint to prevent flash
    (function() {
      const saved = localStorage.getItem('theme');
      const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
      if (saved === 'dark' || (!saved && prefersDark)) {
        document.documentElement.setAttribute('data-theme', 'dark');
      }
    })();
  </script>
  @livewireStyles
  @auth
  <x-onboarding-trigger role="rep" />
  @endauth
</head>
<body>
  <a href="#main" class="skip-link">{{ __('app.skip_to_content') }}</a>
  @auth
    @php
      $notificationSummary = auth()->user()->unreadNotifications()
          ->reorder()
          ->toBase()
          ->selectRaw("COUNT(*) as total, MAX(CASE WHEN data LIKE ? THEN 1 ELSE 0 END) as has_critical", ['%"severity":"critical"%'])
          ->first();
      $unreadNotificationCount = (int) ($notificationSummary->total ?? 0);
      $hasCriticalNotification = (bool) ($notificationSummary->has_critical ?? false);
    @endphp
    <header class="notification-header">
      <a href="/app/profile" class="header-avatar" aria-label="{{ __('app.profile') }}">
        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
      </a>
      <x-_active-company panel="rep" />
      {{-- CG2 offline sync-status badge: pending/failed count from the outbox --}}
      <a href="/app/sync-queue" aria-label="{{ l('قائمة المزامنة', 'Sync queue') }}"
         class="notification-fab"
         x-data="{ pending: 0, failed: 0, conflicts: 0 }"
         x-init="window.addEventListener('jawla-sync-status', e => { pending = e.detail.pending; failed = e.detail.failed; conflicts = e.detail.conflicts; })"
         x-show="pending > 0 || failed > 0 || conflicts > 0"
         style="display:none">
        <svg aria-hidden="true" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12a9 9 0 019-9 9.75 9.75 0 016.74 2.74L21 8m0 0V3m0 5h-5M21 12a9 9 0 01-9 9 9.75 9.75 0 01-6.74-2.74L3 16m0 0v5m0-5h5"/></svg>
        <span aria-live="polite"
              class="notification-badge {{ 'notification-badge-normal' }}"
              :class="failed > 0 || conflicts > 0 ? 'notification-badge-critical' : 'notification-badge-normal'"
              x-text="(pending + failed + conflicts) > 99 ? '99+' : (pending + failed + conflicts)"></span>
      </a>
      <a href="/app/notifications" aria-label="{{ __('app.notifications') }}"
         class="notification-fab">
        <svg aria-hidden="true" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
        @if($unreadNotificationCount > 0)
          <span aria-live="polite"
                class="notification-badge {{ $hasCriticalNotification ? 'notification-badge-critical' : 'notification-badge-normal' }}">
            {{ $unreadNotificationCount > 99 ? '99+' : $unreadNotificationCount }}
          </span>
        @endif
      </a>
    </header>
  @endauth
  <main id="main" data-page="{{ $pageKey }}">{!! $slot !!}</main>
  {{-- iOS PWA back button — visible only in standalone mode where browser chrome is hidden --}}
  <button id="ios-back-btn" class="ios-back-btn" aria-label="{{ l('رجوع', 'Go back') }}" onclick="history.back()" hidden>
    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M15 18l-6-6 6-6"/></svg>
  </button>
  @livewireScripts
  @filamentScripts
  <script defer>
    let deferredPrompt = null;
    window.addEventListener('beforeinstallprompt', e => {
      e.preventDefault();
      deferredPrompt = e;
      setTimeout(() => {
        if (deferredPrompt) {
          const banner = document.createElement('div');
          banner.id = 'pwa-install-banner';
          banner.className = 'pwa-install-banner';
          banner.innerHTML = '<span class="pwa-install-banner-text">{{ l("ثبّت التطبيق", "Install App") }}</span><div><button id="pwa-install-btn" class="pwa-install-btn">{{ l("تثبيت", "Install") }}</button><button id="pwa-dismiss-btn" class="pwa-dismiss-btn">{{ l("لاحقاً", "Later") }}</button></div>';
          document.body.appendChild(banner);
          document.getElementById('pwa-install-btn').addEventListener('click', () => { deferredPrompt.prompt(); deferredPrompt = null; banner.remove(); });
          document.getElementById('pwa-dismiss-btn').addEventListener('click', () => { deferredPrompt = null; banner.remove(); });
        }
      }, 30000);
    });
    window.addEventListener('appinstalled', () => { document.getElementById('pwa-install-banner')?.remove(); });
    // iOS standalone PWA back button — show only in standalone mode with history
    (function() {
      const btn = document.getElementById('ios-back-btn');
      if (!btn) return;
      const isStandalone = window.navigator.standalone === true || window.matchMedia('(display-mode: standalone)').matches;
      if (isStandalone && window.history.length > 1) btn.hidden = false;
    })();
  </script>
  @auth
    {{-- Global undo toast for reversible rep writes (Phase 6 / B1). --}}
    <livewire:app.action-toast />
    {{-- On-shift rep location beacon (CG3). Server enforces on-shift + dedupe. --}}
    <livewire:app.location-tracker />
    {{-- Global offline status (UI/UX §55). Shown only while the device is offline;
         the sync engine still queues writes and auto-flushes on reconnect. --}}
    <div id="offline-indicator" class="offline-indicator" role="status" aria-live="polite" hidden>
      <svg aria-hidden="true" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M1 1l22 22"/><path d="M16.72 11.06A10.94 10.94 0 0119 12.55"/><path d="M5 12.55a10.94 10.94 0 015.17-2.39"/><path d="M10.71 5.05A16 16 0 0122.58 9"/><path d="M1.42 9a15.91 15.91 0 014.7-2.88"/><path d="M8.53 16.11a6 6 0 016.95 0"/><line x1="12" y1="20" x2="12.01" y2="20"/>
      </svg>
      <span>{{ l('غير متصل — يُحفظ عملك على الجهاز ويُزامَن تلقائيًا', 'Offline — saved on your device, syncs automatically') }}</span>
    </div>
    <a id="storage-pressure-indicator"
       class="offline-indicator storage-pressure-indicator"
       href="/app/sync-queue"
       role="alert"
       aria-live="assertive"
       hidden>
      <svg aria-hidden="true" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M12 9v4m0 4h.01M10.3 3.8L1.8 18.5A2 2 0 003.5 21h17a2 2 0 001.7-2.5L13.7 3.8a2 2 0 00-3.4 0z"/>
      </svg>
      <span>{{ l('مساحة الجهاز منخفضة — افتح قائمة المزامنة لحفظ عملياتك بأمان', 'Device storage is low — open the sync queue to protect your work') }}</span>
    </a>
  @endauth
</body>
</html>
