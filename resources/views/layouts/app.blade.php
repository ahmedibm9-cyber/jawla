<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}" style="color-scheme:light">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <meta name="theme-color" content="#4DB848">
  <link rel="manifest" href="/manifest.json">
  <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
  <title>Jawla</title>
  @filamentStyles
  @vite('resources/css/app.css')
  <style>:root{-webkit-tap-highlight-color:transparent}</style>
  @livewireStyles
</head>
<body>
  <a href="#main" class="skip-link">{{ __('app.skip_to_content') }}</a>
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
          banner.innerHTML = '<span style="font-size:.875rem">{{ app()->getLocale() === "ar" ? "ثبّت التطبيق" : "Install App" }}</span><div><button id="pwa-install-btn" style="background:#4DB848;color:#fff;border:none;border-radius:8px;padding:8px 16px;cursor:pointer;font-size:.875rem;margin-right:8px">{{ app()->getLocale() === "ar" ? "تثبيت" : "Install" }}</button><button id="pwa-dismiss-btn" style="background:transparent;color:#9CA3AF;border:none;cursor:pointer;font-size:.875rem">{{ app()->getLocale() === "ar" ? "لاحقاً" : "Later" }}</button></div>';
          document.body.appendChild(banner);
          document.getElementById('pwa-install-btn').addEventListener('click', () => { deferredPrompt.prompt(); deferredPrompt = null; banner.remove(); });
          document.getElementById('pwa-dismiss-btn').addEventListener('click', () => { deferredPrompt = null; banner.remove(); });
        }
      }, 30000);
    });
    window.addEventListener('appinstalled', () => { document.getElementById('pwa-install-banner')?.remove(); });
  </script>
</body>
</html>
