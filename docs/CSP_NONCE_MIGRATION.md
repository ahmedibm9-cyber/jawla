# CSP Nonce Migration Plan

## Current state

The CSP uses `'unsafe-inline'` and `'unsafe-eval'` in `script-src` because
Livewire 3 and Alpine.js v3 require inline scripts and `eval()`.

## Why it matters

- `'unsafe-inline'` allows any inline script to execute — XSS payloads included
- `'unsafe-eval'` allows `eval()`, `new Function()`, `setTimeout(string)` —
  all common XSS vectors
- A nonce-based CSP eliminates both: only scripts with the correct nonce execute

## Migration path

### Step 1: Upgrade to Livewire 4 (when released)

Livewire 4 is expected to add native nonce support:

- Generate per-request nonce: `$nonce = base64_encode(random_bytes(16))`
- Pass to view: `view()->share('cspNonce', $nonce)`
- Livewire automatically adds `nonce` to its `<script>` tags

### Step 2: Upgrade to Alpine.js v4 (when released)

Alpine.js v4 is expected to remove the `eval()` dependency:

- Check release notes for `x-init` and `x-effect` changes
- Test all Alpine components thoroughly after upgrade

### Step 3: Update CSP in SecurityHeaders.php

```php
$nonce = base64_encode(random_bytes(16));
view()->share('cspNonce', $nonce);

$csp = implode('; ', [
    "default-src 'self'",
    "script-src 'self' 'nonce-{$nonce}'",
    "style-src 'self' 'nonce-{$nonce}' https://fonts.googleapis.com https://fonts.bunny.net",
    // ... rest unchanged
]);
```

### Step 4: Add nonce to Blade templates

```html
<script nonce="{{ $cspNonce }}">
  // inline scripts now need the nonce attribute
</script>
```

### Step 5: Update Filament

Filament 4.x may need a service provider hook to inject the nonce:

```php
// AppServiceProvider.php
FilamentView::registerScriptData(fn () => ['nonce' => $cspNonce]);
```

## Testing

1. Deploy with nonce-based CSP
2. Check browser console for CSP violations
3. Test all forms, modals, and dynamic components
4. Test offline mode and service worker
5. Test PWA install prompt

## Rollback

If CSP nonce breaks something:

1. Revert SecurityHeaders.php to previous version
2. Deploy
3. No data loss — CSP is a header-only change

## Timeline

- **Now:** Documented migration path
- **When Livewire 4 releases:** Begin Step 1
- **When Alpine v4 releases:** Complete Step 2
- **Target:** Q1 2027 (dependent on upstream releases)
