<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}" style="color-scheme:light">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#4DB848">
  <link rel="manifest" href="/manifest.json">
  <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
  <title>Jawla</title>
  @filamentStyles
  @vite('resources/css/app.css')
  <style>
    :root{-webkit-tap-highlight-color:transparent}
    body{margin:0;font-family:'IBM Plex Sans Arabic',system-ui,sans-serif;background:var(--clr-surface-alt)}
    .skip-link{position:absolute;left:-9999px;top:0;background:var(--clr-accent);color:#fff;padding:8px 16px;z-index:var(--z-overlay);text-decoration:none;border-radius:0 0 8px 0}
    .skip-link:focus{left:0}
    .tab-bar{position:fixed;bottom:0;left:0;right:0;display:flex;background:var(--clr-surface);border-top:1px solid var(--clr-border-light);z-index:var(--z-dropdown)}
    .tab-item{flex:1;display:flex;flex-direction:column;align-items:center;padding:6px 0 8px;color:var(--clr-text-secondary);font-size:0.7rem;text-decoration:none;touch-action:manipulation}
    .tab-item.active{color:var(--clr-accent)}
    .tab-item svg{width:22px;height:22px;margin-bottom:2px}
    .tab-item:hover,.tab-item:focus-visible{color:var(--clr-accent);outline:none}
    .tab-item:focus-visible{box-shadow:0 -2px 0 0 var(--clr-accent)}
    .main-content{padding-bottom:72px}
    .card{background:var(--clr-surface);border-radius:12px;padding:16px;margin-bottom:12px;box-shadow:var(--shadow-sm)}
    .card:active{background:var(--clr-surface-hover)}
    .btn{display:inline-flex;align-items:center;justify-content:center;padding:10px 20px;border-radius:10px;font-weight:600;font-size:0.95rem;border:none;cursor:pointer;min-height:44px;touch-action:manipulation;transition:background var(--transition-fast),box-shadow var(--transition-fast)}
    .btn:disabled,.btn[disabled]{opacity:0.5;cursor:not-allowed}
    .btn-primary{background:var(--clr-accent);color:#fff}
    .btn-primary:hover:not(:disabled){background:var(--clr-accent-dark)}
    .btn-primary:focus-visible{outline:none;box-shadow:0 0 0 3px color-mix(in srgb,var(--clr-accent) 40%,transparent)}
    .btn-danger{background:var(--clr-danger);color:#fff}
    .btn-danger:hover:not(:disabled){background:#b91c1c}
    .btn-danger:focus-visible{outline:none;box-shadow:0 0 0 3px color-mix(in srgb,var(--clr-danger) 40%,transparent)}
    .btn-outline{border:2px solid var(--clr-accent);color:var(--clr-accent);background:transparent}
    .btn-outline:hover:not(:disabled){background:color-mix(in srgb,var(--clr-accent) 8%,transparent)}
    .btn-outline:focus-visible{outline:none;box-shadow:0 0 0 3px color-mix(in srgb,var(--clr-accent) 30%,transparent)}
    .badge{display:inline-block;padding:2px 8px;border-radius:6px;font-size:0.75rem;font-weight:600}
    .badge-warning{background:#FEF3C7;color:var(--clr-warning)}
    .badge-success{background:#DCFCE7;color:var(--clr-success)}
    .badge-danger{background:#FEE2E2;color:var(--clr-danger)}
    .stepper{display:flex;align-items:center;margin-bottom:20px}
    .step{display:flex;flex-direction:column;align-items:center;flex:1}
    .step-dot{width:28px;height:28px;border-radius:50%;background:var(--clr-border-light);display:flex;align-items:center;justify-content:center;font-size:0.8rem;font-weight:700;color:var(--clr-text-secondary)}
    .step.active .step-dot{background:var(--clr-accent);color:#fff}
    .step.done .step-dot{background:var(--clr-success);color:#fff}
    .step-line{flex:1;height:2px;background:var(--clr-border-light);margin:0 -4px}
    .step.done .step-line,.step.active .step-line{background:var(--clr-accent)}
    .skeleton{background:linear-gradient(90deg,var(--clr-border-light) 25%,var(--clr-surface-hover) 50%,var(--clr-border-light) 75%);background-size:200% 100%;animation:shimmer 1.5s infinite;border-radius:8px}
    @keyframes shimmer{0%{background-position:200% 0}100%{background-position:-200% 0}}
    @media(prefers-reduced-motion:reduce){.skeleton{animation:none}.toast{animation:none}}
    .skeleton-card{height:80px;margin-bottom:12px}
    .toast{position:fixed;top:16px;left:50%;transform:translateX(-50%);padding:12px 24px;border-radius:10px;font-weight:600;z-index:var(--z-toast);box-shadow:var(--shadow-md);animation:fadeIn var(--transition-normal) ease-out}
    .toast-success{background:var(--clr-success);color:#fff}
    .toast-error{background:var(--clr-danger);color:#fff}
    @keyframes fadeIn{from{opacity:0;transform:translateX(-50%) translateY(-8px)}to{opacity:1;transform:translateX(-50%) translateY(0)}}
    .maps-link{display:inline-flex;align-items:center;gap:4px;font-size:0.85rem;color:var(--clr-accent-blue);text-decoration:none;margin-top:4px}
    .maps-link:hover{text-decoration:underline}
    /* ---- interactive cards ---- */
    .clickable-card{cursor:pointer;transition:background var(--transition-fast)}
    .clickable-card:hover{background:var(--clr-surface-hover)}
    .clickable-card:focus-visible{outline:2px solid var(--clr-accent);outline-offset:2px}
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