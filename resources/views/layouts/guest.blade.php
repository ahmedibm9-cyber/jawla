<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#F5F5F4">
  <link rel="manifest" href="/manifest.json">
  <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
  <link rel="stylesheet" href="https://fonts.bunny.net/css?family=ibm-plex-sans-arabic:400,500,700&display=swap">
  <title>Jawla</title>
  <style>
    body { margin: 0; font-family: 'IBM Plex Sans Arabic', system-ui, sans-serif; background: #F5F5F4; }
    .guest-brand { text-align: center; padding: 32px 16px 0; }
    .guest-brand img { height: 40px; width: auto; }
  </style>
</head>
<body>
  <div class="guest-brand">
    <img src="/images/logo.svg" alt="Jawla" width="120" height="40">
  </div>
  {{ $slot ?? '' }}
</body>
</html>
