<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#F8FAFC">
  <link rel="icon" href="/images/green-j.png" type="image/png">
  <link rel="apple-touch-icon" href="/icons/icon-192.png">
  <link rel="manifest" href="/manifest.json">
  <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
  <link rel="stylesheet" href="https://fonts.bunny.net/css?family=ibm-plex-sans-arabic:400,500,700&display=swap">
  <title>Jawla</title>
  <style>
    body { margin: 0; font-family: 'IBM Plex Sans Arabic', system-ui, sans-serif; background: #F8FAFC; }
    .guest-brand { text-align: center; padding: 32px 16px 0; }
    .guest-brand img { height: 56px; width: auto; }
  </style>
</head>
<body>
  <div class="guest-brand">
    <img src="/images/green-j.png" alt="Jawla">
  </div>
  {{ $slot ?? '' }}
</body>
</html>