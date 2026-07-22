<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <meta name="theme-color" content="#0F172A">
  <link rel="icon" href="/images/logo-app-icon.png" type="image/png">
  <link rel="apple-touch-icon" href="/icons/icon-192.png">
  <link rel="manifest" href="/manifest.json">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
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
  <meta name="description" content="{{ app()->getLocale() === 'ar' ? 'تطبيق إدارة المبيعات الميدانية - Jawla' : 'Field Sales Management PWA - Jawla' }}">
  <meta name="robots" content="noindex, nofollow">
  @filamentStyles
  @vite(['resources/css/app.css', 'resources/js/app.js'])
  <style>:root{-webkit-tap-highlight-color:transparent}[x-cloak]{display:none!important}</style>
  @livewireStyles
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
      {{-- CG2 offline sync-status badge: pending/failed count from the outbox --}}
      <a href="/app/sync-queue" aria-label="{{ app()->getLocale() === 'ar' ? 'قائمة المزامنة' : 'Sync queue' }}"
         class="notification-fab"
         x-data="{ pending: 0, failed: 0 }"
         x-init="window.addEventListener('jawla-sync-status', e => { pending = e.detail.pending; failed = e.detail.failed; })"
         x-show="pending > 0 || failed > 0"
         style="display:none">
        <svg aria-hidden="true" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12a9 9 0 019-9 9.75 9.75 0 016.74 2.74L21 8m0 0V3m0 5h-5M21 12a9 9 0 01-9 9 9.75 9.75 0 01-6.74-2.74L3 16m0 0v5m0-5h5"/></svg>
        <span aria-live="polite"
              class="notification-badge {{ 'notification-badge-normal' }}"
              :class="failed > 0 ? 'notification-badge-critical' : 'notification-badge-normal'"
              x-text="(pending + failed) > 99 ? '99+' : (pending + failed)"></span>
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
  <main id="main">{!! $slot !!}</main>
  @livewireScripts
  @filamentScripts
  <script>
    let deferredPrompt = null;
    window.addEventListener('beforeinstallprompt', e => {
      e.preventDefault();
      deferredPrompt = e;
      setTimeout(() => {
        if (deferredPrompt) {
          const banner = document.createElement('div');
          banner.id = 'pwa-install-banner';
          banner.className = 'pwa-install-banner';
          banner.innerHTML = '<span class="pwa-install-banner-text">{{ app()->getLocale() === "ar" ? "ثبّت التطبيق" : "Install App" }}</span><div><button id="pwa-install-btn" class="pwa-install-btn">{{ app()->getLocale() === "ar" ? "تثبيت" : "Install" }}</button><button id="pwa-dismiss-btn" class="pwa-dismiss-btn">{{ app()->getLocale() === "ar" ? "لاحقاً" : "Later" }}</button></div>';
          document.body.appendChild(banner);
          document.getElementById('pwa-install-btn').addEventListener('click', () => { deferredPrompt.prompt(); deferredPrompt = null; banner.remove(); });
          document.getElementById('pwa-dismiss-btn').addEventListener('click', () => { deferredPrompt = null; banner.remove(); });
        }
      }, 30000);
    });
    window.addEventListener('appinstalled', () => { document.getElementById('pwa-install-banner')?.remove(); });
  </script>
  <script>
    // Skip the service worker under automated browsers (navigator.webdriver):
    // its shell caching keeps the page from reaching network-idle, which hangs
    // E2E tests. Real users are unaffected.
    if ('serviceWorker' in navigator && !navigator.webdriver) {
      window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch(() => {});
      });
    }
  </script>
  @auth
    {{-- Global undo toast for reversible rep writes (Phase 6 / B1). --}}
    <livewire:app.action-toast />
    {{-- On-shift rep location beacon (CG3). Server enforces on-shift + dedupe. --}}
    <livewire:app.location-tracker />
  @endauth
</body>
</html>
