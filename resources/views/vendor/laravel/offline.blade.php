<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Jawla — Offline</title>
<style>
body{font-family:system-ui,sans-serif;margin:0;display:flex;align-items:center;justify-content:center;min-height:100dvh;background:#F5F5F4;text-align:center;padding:24px}
.card{background:#fff;border-radius:16px;padding:32px;max-width:360px;box-shadow:0 2px 8px rgba(0,0,0,.08)}
.icon{font-size:48px;margin-bottom:16px}
h1{font-size:1.25rem;margin:0 0 8px;color:#1F2937}
p{color:#6B7280;margin:0 0 16px;font-size:.875rem;line-height:1.5}
.btn{display:inline-block;padding:10px 24px;background:#9B1C31;color:#fff;border-radius:8px;text-decoration:none;font-size:.875rem}
</style>
</head>
<body>
<div class="card">
    <div class="icon">&#9888;</div>
    <h1>{{ app()->getLocale() === 'ar' ? 'لا يوجد اتصال بالإنترنت' : 'No Internet Connection' }}</h1>
    <p>{{ app()->getLocale() === 'ar' ? 'يرجى التحقق من اتصالك بالإنترنت والمحاولة مرة أخرى' : 'Please check your connection and try again' }}</p>
    <a class="btn" href="javascript:location.reload()">{{ app()->getLocale() === 'ar' ? 'إعادة المحاولة' : 'Retry' }}</a>
</div>
</body>
</html>
