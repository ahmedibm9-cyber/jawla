<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="theme-color" content="#9B1C31"><title>{{ __('errors.404') }} - Jawla</title><style>body{font-family:system-ui;padding:2rem;display:flex;flex-direction:column;min-height:100vh;margin:0;justify-content:center;align-items:center}main{text-align:center}nav a{margin-top:1rem;display:inline-block;padding:.5rem 1rem;background:#9B1C31;color:#fff;text-decoration:none;border-radius:6px}</style></head>
<body><a href="#main" class="skip-link" style="position:absolute;left:-9999px">Skip to content</a><main id="main"><h1>{{ __('errors.404') }}</h1><p>{{ __('errors.404_description') }}</p><nav aria-label="Home"><a href="/">{{ __('errors.go_home') }}</a></nav></main></body>
</html>
