<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#4DB848">
  <link rel="manifest" href="/manifest.json">
  <title>Jawla</title>
</head>
<body>
  <header style="display:flex;justify-content:space-between;align-items:center;padding:.5rem 1rem;">
    <span>Jawla</span>
    <nav>
      <a href="{{ route('locale.switch', app()->getLocale() === 'ar' ? 'en' : 'ar') }}">
        {{ app()->getLocale() === 'ar' ? 'English' : 'العربية' }}
      </a>
      @auth
        <form method="POST" action="{{ route('app.logout') }}" style="display:inline">
          @csrf
          <button type="submit">{{ __('app.logout') }}</button>
        </form>
      @endauth
    </nav>
  </header>
  {{ $slot ?? '' }}
</body>
</html>
