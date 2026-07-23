<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <meta name="theme-color" content="#0F172A">
  <meta name="robots" content="noindex, nofollow">
  <title>{{ __('auth.login') }} | Jawla</title>
  <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
  <link rel="icon" href="/images/logo-app-icon.webp" type="image/webp">
  @vite(['resources/css/app.css'])
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    .login-page {
      min-height: 100dvh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 24px 16px;
      background:
        radial-gradient(ellipse at 20% 0%, rgba(61, 122, 24, 0.08) 0%, transparent 50%),
        radial-gradient(ellipse at 80% 100%, rgba(61, 122, 24, 0.05) 0%, transparent 50%),
        var(--color-surface-alt);
      font-family: var(--font-sans);
    }
    .login-card {
      width: 100%;
      max-width: 400px;
      background: var(--color-surface);
      border-radius: 20px;
      padding: 40px 32px;
      box-shadow:
        0 1px 3px rgba(0, 0, 0, 0.04),
        0 4px 12px rgba(0, 0, 0, 0.06),
        0 12px 32px rgba(0, 0, 0, 0.08);
      border: 1px solid var(--color-border-light);
      position: relative;
      overflow: hidden;
    }
    .login-card::before {
      content: "";
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 4px;
      background: linear-gradient(90deg, var(--color-brand) 0%, #6DB83B 50%, var(--color-brand-dark) 100%);
    }
    .login-brand {
      text-align: center;
      margin-bottom: 32px;
    }
    .login-logo {
      height: 52px;
      width: auto;
      margin-bottom: 12px;
    }
    @media (prefers-color-scheme: dark) {
      .login-logo { display: none; }
      .login-logo-dark { display: inline-block !important; }
    }
    .login-logo-dark { display: none; height: 52px; width: auto; margin-bottom: 12px; }
    .login-title {
      font-size: 1.5rem;
      font-weight: 700;
      color: var(--color-text-primary);
      line-height: 1.3;
      margin: 0;
    }
    .login-subtitle {
      font-size: 0.875rem;
      color: var(--color-text-muted);
      margin-top: 4px;
    }
    .login-form { margin-top: 0; }
    .login-field {
      margin-bottom: 20px;
    }
    .login-label {
      display: block;
      font-size: 0.8125rem;
      font-weight: 600;
      color: var(--color-text-secondary);
      margin-bottom: 8px;
    }
    .login-input-wrap {
      position: relative;
    }
    .login-input-icon {
      position: absolute;
      top: 50%;
      transform: translateY(-50%);
      width: 20px;
      height: 20px;
      color: var(--color-text-muted);
      pointer-events: none;
      transition: color var(--transition-fast);
    }
    [dir="ltr"] .login-input-icon { left: 14px; }
    [dir="rtl"] .login-input-icon { right: 14px; }
    .login-input {
      width: 100%;
      padding: 14px 14px 14px 44px;
      border: 1.5px solid var(--color-border);
      border-radius: 14px;
      font-size: 0.9375rem;
      font-family: inherit;
      color: var(--color-text-primary);
      background: var(--color-surface);
      transition: border-color var(--transition-fast), box-shadow var(--transition-fast);
      -webkit-appearance: none;
      appearance: none;
    }
    [dir="rtl"] .login-input { padding: 14px 44px 14px 14px; }
    .login-input:focus {
      outline: none;
      border-color: var(--color-brand);
      box-shadow: 0 0 0 3px color-mix(in srgb, var(--color-brand) 15%, transparent);
    }
    .login-input::placeholder { color: var(--color-text-muted); }
    .login-remember {
      display: flex;
      align-items: center;
      gap: 8px;
      margin-bottom: 24px;
    }
    .login-remember input[type="checkbox"] {
      width: 18px;
      height: 18px;
      accent-color: var(--color-brand);
      border-radius: 4px;
      cursor: pointer;
    }
    .login-remember label {
      font-size: 0.875rem;
      color: var(--color-text-secondary);
      cursor: pointer;
      user-select: none;
    }
    .login-submit {
      width: 100%;
      padding: 14px 24px;
      border: none;
      border-radius: 14px;
      font-size: 1rem;
      font-weight: 700;
      font-family: inherit;
      color: #fff;
      background: linear-gradient(135deg, var(--color-brand) 0%, var(--color-brand-dark) 100%);
      box-shadow: 0 2px 8px rgba(109, 184, 59, 0.25);
      cursor: pointer;
      min-height: 52px;
      transition: background var(--transition-fast), box-shadow var(--transition-fast), transform var(--transition-fast);
      -webkit-tap-highlight-color: transparent;
    }
    .login-submit:hover:not(:disabled) {
      background: linear-gradient(135deg, var(--color-brand-light) 0%, var(--color-brand) 100%);
      box-shadow: 0 4px 12px rgba(109, 184, 59, 0.35);
    }
    .login-submit:active:not(:disabled) { transform: scale(0.98); }
    .login-submit:focus-visible {
      outline: none;
      box-shadow: 0 0 0 3px color-mix(in srgb, var(--color-brand) 40%, transparent);
    }
    .login-submit:disabled { opacity: 0.6; cursor: not-allowed; }
    .login-error {
      background: #fef2f2;
      border: 1px solid #fecaca;
      color: #991b1b;
      padding: 12px 16px;
      border-radius: 12px;
      font-size: 0.875rem;
      margin-bottom: 20px;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    [dir="rtl"] .login-error { direction: rtl; }
    .login-error svg { width: 18px; height: 18px; flex-shrink: 0; }
    .login-footer {
      text-align: center;
      margin-top: 24px;
      font-size: 0.8125rem;
      color: var(--color-text-muted);
    }
    @media (prefers-reduced-motion: reduce) {
      .login-submit { transition: none; }
    }
    @media (max-width: 400px) {
      .login-card { padding: 32px 20px; border-radius: 16px; }
    }
    @media (prefers-color-scheme: dark) {
      .login-error { background: #450a0a; border-color: #7f1d1d; color: #fca5a5; }
    }
  </style>
</head>
<body>
  <div class="login-page">
    <div class="login-card">
      <div class="login-brand">
        <img src="{{ asset('images/black-j.webp') }}" alt="Jawla" class="login-logo" height="52">
        <img src="{{ asset('images/white-j.webp') }}" alt="Jawla" class="login-logo-dark" height="52">
        <h1 class="login-title">{{ __('auth.welcome_back') }}</h1>
        <p class="login-subtitle">{{ __('auth.login_subtitle') }}</p>
      </div>

      @if ($errors->any())
        <div class="login-error" role="alert">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
          <span>{{ $errors->first() }}</span>
        </div>
      @endif

      <form method="POST" action="{{ route('login.post') }}" class="login-form" autocomplete="on">
        @csrf

        <div class="login-field">
          <label for="email" class="login-label">{{ __('auth.email') }}</label>
          <div class="login-input-wrap">
            <svg class="login-input-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/></svg>
            <input type="email" name="email" id="email" class="login-input" value="{{ old('email') }}" required autofocus autocomplete="email" spellcheck="false" placeholder="{{ __('auth.email_placeholder') }}">
          </div>
        </div>

        <div class="login-field">
          <label for="password" class="login-label">{{ __('auth.password_label') }}</label>
          <div class="login-input-wrap">
            <svg class="login-input-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/></svg>
            <input type="password" name="password" id="password" class="login-input" required autocomplete="current-password" placeholder="{{ __('auth.password_placeholder') }}">
          </div>
        </div>

        <div class="login-remember">
          <input type="checkbox" name="remember" id="remember" value="1">
          <label for="remember">{{ __('auth.remember_me') }}</label>
        </div>

        <button type="submit" class="login-submit">
          {{ __('auth.login') }}
        </button>
      </form>

      <p class="login-footer">Jawla &copy; {{ date('Y') }}</p>
    </div>
  </div>
</body>
</html>
