<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="icon" href="/images/logo-app-icon.webp" type="image/png">
<title>Jawla — Offline</title>
<style>
body{font-family:'IBM Plex Sans Arabic',system-ui,sans-serif;margin:0;display:flex;align-items:center;justify-content:center;min-height:100dvh;background:#F8FAFC;text-align:center;padding:24px}
.card{background:#fff;border-radius:16px;padding:32px;max-width:360px;box-shadow:0 2px 8px rgba(0,0,0,.08)}
.card img{height:56px;width:auto;margin-bottom:16px}
h1{font-size:1.25rem;margin:0 0 8px;color:#0F172A}
p{color:#475569;margin:0 0 16px;font-size:.875rem;line-height:1.5}
.btn{display:inline-block;padding:10px 24px;background:#6DB83B;color:#fff;border-radius:8px;text-decoration:none;font-size:.875rem}
</style>
</head>
<body>
<div class="card">
    <img src="/images/logo-app-icon.webp" alt="Jawla">
    <h1>{{ app()->getLocale() === 'ar' ? 'لا يوجد اتصال بالإنترنت' : 'No Internet Connection' }}</h1>
    <p>{{ app()->getLocale() === 'ar' ? 'يرجى التحقق من اتصالك بالإنترنت والمحاولة مرة أخرى' : 'Please check your connection and try again' }}</p>
    <a class="btn" href="javascript:location.reload()">{{ app()->getLocale() === 'ar' ? 'إعادة المحاولة' : 'Retry' }}</a>
</div>
</body>
</html>