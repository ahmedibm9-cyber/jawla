<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#9B1C31">
  <link rel="manifest" href="/manifest.json">
  <title>Jawla</title>
</head>
<body>{{ $slot ?? '' }}</body>
</html>
