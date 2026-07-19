<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}" style="color-scheme:light">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <meta name="theme-color" content="#F5F5F4">
  <link rel="manifest" href="/manifest.json">
  <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
  @php
    $pageKeyMap = [
        'home' => 'home', 'visits' => 'visits', 'visit' => 'visits',
        'customers' => 'customers', 'customers.create' => 'customers',
        'orders' => 'orders', 'stock' => 'stock', 'more' => 'more',
        'notifications' => 'notifications', 'quotations' => 'quotations',
        'sell' => 'create_invoice', 'sell.customer' => 'create_invoice',
        'collect-payment' => 'collect_payment', 'returns' => 'log_return',
        'expenses' => 'log_expense',
    ];
    $pageKey = \Illuminate\Support\Str::after(request()->route()?->getName() ?? '', 'app.');
  @endphp
  <title>{{ isset($pageKeyMap[$pageKey]) ? __('app.'.$pageKeyMap[$pageKey]).' | Jawla' : 'Jawla' }}</title>
  <meta name="description" content="{{ app()->getLocale() === 'ar' ? 'تطبيق إدارة المبيعات الميدانية - Jawla' : 'Field Sales Management PWA - Jawla' }}">
  <meta name="robots" content="noindex, nofollow">
  @filamentStyles
  @vite('resources/css/app.css')
  <style>:root{-webkit-tap-highlight-color:transparent}[x-cloak]{display:none!important}</style>
  @livewireStyles
</head>
<body>
  <a href="#main" class="skip-link">{{ __('app.skip_to_content') }}</a>
  @auth
    @php
      $unreadNotificationCount = auth()->user()->unreadNotifications()->count();
      $hasCriticalNotification = $unreadNotificationCount > 0
          && auth()->user()->unreadNotifications()->where('data', 'like', '%"severity":"critical"%')->exists();
    @endphp
    <header style="position:sticky;top:0;z-index:40;display:flex;justify-content:flex-end;padding:6px 12px;background:transparent;pointer-events:none">
      <a href="/app/notifications" aria-label="{{ __('app.notifications') }}"
         style="pointer-events:auto;position:relative;display:inline-flex;align-items:center;justify-content:center;width:40px;height:40px;border-radius:50%;background:rgba(255,255,255,.92);box-shadow:0 1px 4px rgba(0,0,0,.15);color:#1F2937;text-decoration:none">
        <svg aria-hidden="true" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
        @if($unreadNotificationCount > 0)
          <span aria-live="polite"
                style="position:absolute;top:-4px;inset-inline-end:-4px;min-width:18px;height:18px;padding:0 4px;border-radius:9px;font-size:11px;font-weight:700;line-height:18px;text-align:center;color:#fff;background:{{ $hasCriticalNotification ? '#DC2626' : '#4DB848' }}">
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
          banner.style.cssText = 'position:fixed;bottom:80px;left:16px;right:16px;background:#1F2937;color:#fff;border-radius:12px;padding:16px;display:flex;align-items:center;justify-content:space-between;z-index:1000;box-shadow:0 4px 12px rgba(0,0,0,.2)';
          banner.innerHTML = '<span style="font-size:.875rem">{{ app()->getLocale() === "ar" ? "ثبّت التطبيق" : "Install App" }}</span><div><button id="pwa-install-btn" style="background:#4DB848;color:#fff;border:none;border-radius:8px;padding:8px 16px;cursor:pointer;font-size:.875rem;margin-inline-end:8px">{{ app()->getLocale() === "ar" ? "تثبيت" : "Install" }}</button><button id="pwa-dismiss-btn" style="background:transparent;color:#9CA3AF;border:none;cursor:pointer;font-size:.875rem">{{ app()->getLocale() === "ar" ? "لاحقاً" : "Later" }}</button></div>';
          document.body.appendChild(banner);
          document.getElementById('pwa-install-btn').addEventListener('click', () => { deferredPrompt.prompt(); deferredPrompt = null; banner.remove(); });
          document.getElementById('pwa-dismiss-btn').addEventListener('click', () => { deferredPrompt = null; banner.remove(); });
        }
      }, 30000);
    });
    window.addEventListener('appinstalled', () => { document.getElementById('pwa-install-banner')?.remove(); });
  </script>
  <script>
    if ('serviceWorker' in navigator) {
      window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch(() => {});
      });
    }
  </script>
</body>
</html>
