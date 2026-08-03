# Implementation Plan: Vertical Slice 1 - Login Rate Limiting

## Current State Analysis

### Existing Rate Limiting

1. **Livewire `rateLimit(5)`** - 5 attempts/min per IP/session (in-memory)
2. **Named `login` limiter** - Defined in `AppServiceProvider` (5/min, email+ip keyed) but **dead code** (not wired to any route)
3. **`ThrottlePost` middleware** - 60/min per IP/user (applied to all POST routes including login)

### Gaps Identified

1. **Dead code:** Named `login` limiter not wired to any route
2. **Too generous:** 60 POST/min allows 60 password guesses/min
3. **No persistent failure tracking:** Failures only in memory, reset after 60s
4. **No account lockout:** Users never locked after N failures
5. **No delay escalation:** Each attempt takes same time regardless of failures

## Implementation Strategy

### Approach: Minimal, Focused Fix

Wire the existing `login` rate limiter to the Filament login route. This is the smallest change that addresses the core issue (60/min too generous).

### Future Enhancements (Not in This Slice)

- Persistent failure tracking (requires new table/column)
- Account lockout (requires user status changes)
- Delay escalation (requires response time manipulation)

## Detailed Implementation

### Step 1: Wire Named `login` Rate Limiter to Filament Login

**File:** `app/Providers/Filament/AdminPanelProvider.php`

Add `throttle:login` middleware to the Filament panel middleware stack.

**Current code (lines 94-111):**

```php
->middleware([
    EncryptCookies::class,
    AddQueuedCookiesToResponse::class,
    StartSession::class,
    AuthenticateSession::class,
    ShareErrorsFromSession::class,
    PreventRequestForgery::class,
    SubstituteBindings::class,
    SetActiveCompanyContext::class,
    DisableBladeIconComponents::class,
    DispatchServingFilamentEvent::class,
    SecurityHeaders::class,
    ThrottlePost::class,  // 60/min - too generous for login
])
```

**New code:**

```php
->middleware([
    EncryptCookies::class,
    AddQueuedCookiesToResponse::class,
    StartSession::class,
    AuthenticateSession::class,
    ShareErrorsFromSession::class,
    PreventRequestForgery::class,
    SubstituteBindings::class,
    SetActiveCompanyContext::class,
    DisableBladeIconComponents::class,
    DispatchServingFilamentEvent::class,
    SecurityHeaders::class,
    ThrottlePost::class,
    'throttle:login',  // 5/min per email+ip - stricter for login
])
```

**Note:** The `throttle:login` middleware will apply to all Filament routes, but the rate limiter only triggers when the `email` input is present (which only happens on login POST). For other Filament routes, the `email` input is null, so the rate limiter won't affect them.

### Step 2: Update `ThrottlePost` to Exclude Login Routes

**File:** `app/Http/Middleware/ThrottlePost.php`

Option A: Remove `ThrottlePost` from Filament middleware (since `throttle:login` handles login)
Option B: Keep both (defense in depth)

**Recommendation:** Option A - Remove `ThrottlePost` from Filament panel since `throttle:login` provides stricter protection for login and no protection is needed for other Filament routes (they require authentication).

### Step 3: Create Login Rate Limiting Test

**File:** `tests/Feature/Auth/LoginRateLimitingTest.php`

Test cases:

1. 5 login attempts with same email+IP → 6th returns 429
2. Different IPs → independent limits
3. Different emails → independent limits
4. Rate limit headers present (X-RateLimit-Remaining, Retry-After)
5. Rate limit resets after 1 minute

### Step 4: Update Documentation

**File:** `SECURITY.md`

Add section on login rate limiting:

- 5 attempts per minute per email+IP
- Returns 429 with Retry-After header
- Rate limit key: `email|ip`

**File:** `.env.example`

Add rate limiting configuration if needed (currently hardcoded in `AppServiceProvider`).

## Acceptance Criteria

### Functional Requirements

- [ ] Login attempts limited to 5 per minute per email+IP combination
- [ ] 6th attempt returns HTTP 429 Too Many Requests
- [ ] Response includes `Retry-After` header with seconds until reset
- [ ] Different email addresses have independent limits
- [ ] Different IP addresses have independent limits
- [ ] Rate limit resets after 1 minute window

### Non-Functional Requirements

- [ ] No performance impact on login (rate limit check is fast)
- [ ] Works with Livewire (existing Livewire rate limiting still works)
- [ ] Works with Filament (new throttle:login middleware applied)
- [ ] Bilingual error messages (AR/EN) for rate limit exceeded

### Test Requirements

- [ ] Unit tests for rate limiter logic
- [ ] Feature tests for login endpoint
- [ ] Integration tests with Livewire
- [ ] All existing tests pass (no regressions)

## Risk Assessment

### Low Risk

- **Wiring existing limiter:** The `login` rate limiter already exists in `AppServiceProvider`, just not wired
- **Minimal code change:** Only middleware registration change
- **Reversible:** Can remove middleware if issues arise

### Mitigations

- **Test thoroughly:** Ensure rate limiting works with Livewire
- **Monitor production:** Watch for false positives
- **Document clearly:** Explain rate limiting in SECURITY.md

## Implementation Checklist

- [ ] Step 1: Add `'throttle:login'` to Filament panel middleware
- [ ] Step 2: Remove `ThrottlePost::class` from Filament panel middleware (optional)
- [ ] Step 3: Create `LoginRateLimitingTest.php` with all test cases
- [ ] Step 4: Update `SECURITY.md` with rate limiting documentation
- [ ] Step 5: Run full test suite to ensure no regressions
- [ ] Step 6: Manual testing with browser/curl

## Verification Commands

```bash
# Run login rate limiting tests
php artisan test --filter=LoginRateLimiting

# Run all auth tests
php artisan test --filter=Auth

# Run full test suite
php artisan test

# Manual test with curl
for i in {1..6}; do
  echo "Attempt $i:"
  curl -s -o /dev/null -w "%{http_code}" -X POST http://localhost:8000/admin/login \
    -d "email=test@example.com&password=wrong"
  echo ""
done
```

## Success Metrics

- Login rate limiting active: 5 attempts/min per email+IP
- No false positives on legitimate login attempts
- No performance degradation
- All tests passing
- Documentation updated

---

## Next Steps After This Slice

1. **Vertical Slice 2: Sanctum Token Expiration** - Set finite token expiration
2. **Vertical Slice 3: CSP Hardening** - Remove unsafe-inline/unsafe-eval
3. **Vertical Slice 4: Device UUID Security** - Encrypt device identification cookie
4. **Vertical Slice 5: Trusted Proxy Configuration** - Restrict trusted proxies
5. **Vertical Slice 6: Password Reset Rate Limiting** - Add IP-based rate limiting
6. **Vertical Slice 7: CI DAST on PRs** - Run OWASP ZAP on pull requests
7. **Vertical Slice 8: Session Security** - Harden session configuration
