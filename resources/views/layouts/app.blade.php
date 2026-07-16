<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}" style="color-scheme:light">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#4DB848">
  <link rel="manifest" href="/manifest.json">
  <title>Jawla</title>
  @filamentStyles
  @vite('resources/css/app.css')
  <style>
    :root{-webkit-tap-highlight-color:transparent}
    body{margin:0;font-family:'IBM Plex Sans Arabic',system-ui,sans-serif;background:#F5F5F4}
    .skip-link{position:absolute;left:-9999px;top:0;background:#4DB848;color:#fff;padding:8px 16px;z-index:200;text-decoration:none;border-radius:0 0 8px 0}
    .skip-link:focus{left:0}
    .tab-bar{position:fixed;bottom:0;left:0;right:0;display:flex;background:#fff;border-top:1px solid #e5e7eb;z-index:50}
    .tab-item{flex:1;display:flex;flex-direction:column;align-items:center;padding:6px 0 8px;color:#6b7280;font-size:0.7rem;text-decoration:none;touch-action:manipulation}
    .tab-item.active{color:#4DB848}
    .tab-item svg{width:22px;height:22px;margin-bottom:2px}
    .tab-item:hover,.tab-item:focus-visible{color:#4DB848;outline:none}
    .tab-item:focus-visible{box-shadow:0 -2px 0 0 #4DB848}
    .main-content{padding-bottom:72px}
    .card{background:#fff;border-radius:12px;padding:16px;margin-bottom:12px;box-shadow:0 1px 3px rgba(0,0,0,0.05)}
    .btn{display:inline-flex;align-items:center;justify-content:center;padding:10px 20px;border-radius:10px;font-weight:600;font-size:0.95rem;border:none;cursor:pointer;min-height:44px;touch-action:manipulation;transition:background 0.15s ease-out,box-shadow 0.15s ease-out}
    .btn-primary{background:#4DB848;color:#fff}
    .btn-primary:hover{background:#3da53f}
    .btn-primary:focus-visible{outline:none;box-shadow:0 0 0 3px rgba(77,184,72,0.4)}
    .btn-danger{background:#DC2626;color:#fff}
    .btn-danger:hover{background:#b91c1c}
    .btn-danger:focus-visible{outline:none;box-shadow:0 0 0 3px rgba(220,38,38,0.4)}
    .btn-outline{border:2px solid #4DB848;color:#4DB848;background:transparent}
    .btn-outline:hover{background:rgba(77,184,72,0.08)}
    .btn-outline:focus-visible{outline:none;box-shadow:0 0 0 3px rgba(77,184,72,0.3)}
    .badge{display:inline-block;padding:2px 8px;border-radius:6px;font-size:0.75rem;font-weight:600}
    .badge-warning{background:#FEF3C7;color:#D97706}
    .badge-success{background:#DCFCE7;color:#16A34A}
    .badge-danger{background:#FEE2E2;color:#DC2626}
    .stepper{display:flex;align-items:center;margin-bottom:20px}
    .step{display:flex;flex-direction:column;align-items:center;flex:1}
    .step-dot{width:28px;height:28px;border-radius:50%;background:#e5e7eb;display:flex;align-items:center;justify-content:center;font-size:0.8rem;font-weight:700;color:#6b7280}
    .step.active .step-dot{background:#4DB848;color:#fff}
    .step.done .step-dot{background:#16A34A;color:#fff}
    .step-line{flex:1;height:2px;background:#e5e7eb;margin:0 -4px}
    .step.done .step-line,.step.active .step-line{background:#4DB848}
    .skeleton{background:linear-gradient(90deg,#e5e7eb 25%,#f3f4f6 50%,#e5e7eb 75%);background-size:200% 100%;animation:shimmer 1.5s infinite;border-radius:8px}
    @keyframes shimmer{0%{background-position:200% 0}100%{background-position:-200% 0}}
    @media(prefers-reduced-motion:reduce){.skeleton{animation:none}.toast{animation:none}}
    .skeleton-card{height:80px;margin-bottom:12px}
    .toast{position:fixed;top:16px;left:50%;transform:translateX(-50%);padding:12px 24px;border-radius:10px;font-weight:600;z-index:100;box-shadow:0 4px 12px rgba(0,0,0,0.1);animation:fadeIn 0.2s ease-out}
    .toast-success{background:#16A34A;color:#fff}
    .toast-error{background:#DC2626;color:#fff}
    @keyframes fadeIn{from{opacity:0;transform:translateX(-50%) translateY(-8px)}to{opacity:1;transform:translateX(-50%) translateY(0)}}
    .maps-link{display:inline-flex;align-items:center;gap:4px;font-size:0.85rem;color:#2C6FB4;text-decoration:none;margin-top:4px}
    .maps-link:hover{text-decoration:underline}
  </style>
  @livewireStyles
</head>
<body>
  <a href="#main" class="skip-link">{{ __('app.skip_to_content') }}</a>
  <main id="main">{!! $slot !!}</main>
  @livewireScripts
  @filamentScripts
</body>
</html>