# V-UltraPlan: Security Hardening for Jawla

## Mission

Fix all identified security gaps in the jawla Laravel CRM/ERP project to achieve production-ready security posture.

## Preconditions

- Codebase fully explored with security audit completed
- Current security controls documented
- Risk register reviewed (PR-003 through PR-031)
- Owner decisions pending on 38 security questions

## Scope

Fix all 10 identified security gaps from the audit:

1. **HIGH:** No MFA/2FA implementation
2. **HIGH:** No login rate limiting (60/min too generous)
3. **HIGH:** Sanctum token expiration = null
4. **MEDIUM:** CSP uses unsafe-inline + unsafe-eval
5. **MEDIUM:** Device UUID is unencrypted cookie
6. **MEDIUM:** Trusted proxies at * (overly broad)
7. **MEDIUM:** No password reset rate limiting
8. **LOW:** No CI DAST on PRs
9. **LOW:** SESSION_SECURE_COOKIE not in .env.example
10. **LOW:** No SESSION_SAMESITE=strict

## Non-Goals

- Full MFA rollout for all users (start with admin panel)
- Complete CSP migration (requires Livewire 4 nonce support)
- Fix all 38 owner decisions (out of scope for this plan)
- Address PR-003 through PR-031 risk register items (separate initiative)

## Acceptance Criteria

Each fix must:

1. Pass existing test suite (no regressions)
2. Include new tests for the specific fix
3. Update documentation (SECURITY.md, .env.example)
4. Be reversible without data loss
5. Not break existing functionality

---

## Vertical Slice 1: Login Rate Limiting (HIGH)

**Objective:** Implement 5-minute per IP+email rate limiting on login endpoint

**Actor:** Unauthenticated user
**Preconditions:** Login form exists, ThrottlePost middleware active
**Action:** Submit login form with invalid credentials
**Expected Result:** After 5 attempts from same IP+email, returns 429 Too Many Requests
**Data Changes:** None (rate limit stored in session/cache)
**Permission Checks:** None (unauthenticated)
**Loading/Error States:** 429 response with retry-after header
**Compatibility:** No breaking changes
**Migration:** None
**Accessibility:** Rate limit message must be bilingual (AR/EN)
**Offline:** N/A

**Implementation:**

1. Create `app/Http/Middleware/ThrottleLogin.php`
2. Apply to login route in `routes/web.php`
3. Configure: 5 attempts per minute, keyed by IP+email
4. Add rate limit headers (X-RateLimit-Remaining, Retry-After)
5. Log rate limit hits for security monitoring

**Tests:**

- Attempt 5 logins with same IP+email → 429 on 6th
- Different IPs → independent limits
- Different emails → independent limits
- Valid login resets counter

**Verification:**

```bash
php artisan test --filter=ThrottleLogin
```

---

## Vertical Slice 2: Sanctum Token Expiration (HIGH)

**Objective:** Set finite token expiration for API tokens

**Actor:** Admin user
**Preconditions:** Sanctum configured, tokens being issued
**Action:** Create API token
**Expected Result:** Token expires after configured time (default: 24 hours)
**Data Changes:** `personal_access_tokens.expires_at` populated
**Permission Checks:** Admin can configure expiration
**Loading/Error States:** Expired tokens return 401
**Compatibility:** Existing tokens unaffected until they expire
**Migration:** Add `expires_at` column to `personal_access_tokens` if missing
**Accessibility:** N/A
**Offline:** N/A

**Implementation:**

1. Update `config/sanctum.php`: `'expiration' => 1440` (24 hours)
2. Add `SANCTUM_TOKEN_EXPIRATION` to `.env.example`
3. Create migration to backfill `expires_at` for existing tokens
4. Add token cleanup command: `php artisan tokens:cleanup`
5. Update API token creation to set `expires_at`

**Tests:**

- Create token → expires_at is set
- Access API after expiration → 401
- Refresh token → new expiration
- Cleanup command removes expired tokens

**Verification:**

```bash
php artisan test --filter=SanctumTokenExpiration
```

---

## Vertical Slice 3: CSP Hardening (MEDIUM)

**Objective:** Reduce CSP weaknesses while maintaining functionality

**Actor:** Developer
**Preconditions:** Current CSP uses unsafe-inline/unsafe-eval
**Action:** Update CSP configuration
**Expected Result:** CSP with nonce-based scripts (where possible), unsafe-eval removed
**Data Changes:** None
**Permission Checks:** N/A
**Loading/Error Styles:** None
**Compatibility:** Must not break Livewire/Alpine.js
**Migration:** None
**Accessibility:** N/A
**Offline:** N/A

**Implementation:**

1. Generate CSP nonce per request in `SecurityHeaders.php`
2. Add nonce to Livewire scripts (if Livewire 4 supports it)
3. Remove `unsafe-eval` from CSP (test if Livewire works without it)
4. Pin `unpkg.com` origin (remove wildcard)
5. Add CSP violation reporting endpoint

**Tests:**

- CSP header includes nonce
- No `unsafe-eval` in production
- Livewire functionality preserved
- CSP violations reported

**Verification:**

```bash
php artisan test --filter=SecurityHeaders
```

---

## Vertical Slice 4: Device UUID Security (MEDIUM)

**Objective:** Encrypt device identification cookie

**Actor:** User
**Preconditions:** Device approval system active
**Action:** Login from new device
**Expected Result:** Device ID is encrypted/signed, not readable by client
**Data Changes:** None
**Permission Checks:** Device must be approved
**Loading/Error States:** Invalid device ID rejected
**Compatibility:** Existing devices remain valid
**Migration:** None
**Accessibility:** N/A
**Offline:** N/A

**Implementation:**

1. Update `EnsureApprovedDevice.php` to use encrypted cookies
2. Generate device fingerprint from user agent + IP range
3. Sign device ID with application key
4. Add device rotation mechanism
5. Log device changes for security

**Tests:**

- Device cookie is encrypted
- Tampered device ID rejected
- Device approval still works
- Device rotation works

**Verification:**

```bash
php artisan test --filter=EnsureApprovedDevice
```

---

## Vertical Slice 5: Trusted Proxy Configuration (MEDIUM)

**Objective:** Restrict trusted proxies to known infrastructure

**Actor:** System administrator
**Preconditions:** Application deployed behind proxy
**Action:** Configure trusted proxies
**Expected Result:** Only known proxy IPs trusted, not all (`*`)
**Data Changes:** None
**Permission Checks:** N/A
**Loading/Error States:** None
**Compatibility:** Must work with Railway/container deployment
**Migration:** None
**Accessibility:** N/A
**Offline:** N/A

**Implementation:**

1. Update `bootstrap/app.php` to use `TRUSTED_PROXIES` env var
2. Add `TRUSTED_PROXIES` to `.env.example` with Railway proxy IPs
3. Document proxy configuration in SECURITY.md
4. Add validation for proxy IP format
5. Log proxy header changes for monitoring

**Tests:**

- Trusted proxies configured from env
- Unknown proxy IPs rejected
- Forwarded headers preserved for known proxies
- Proxy changes logged

**Verification:**

```bash
php artisan test --filter=TrustedProxies
```

---

## Vertical Slice 6: Password Reset Rate Limiting (MEDIUM)

**Objective:** Add IP-based rate limiting to password reset requests

**Actor:** User
**Preconditions:** Password reset form exists
**Action:** Request password reset
**Expected Result:** Limited to 3 requests per IP per hour
**Data Changes:** None (rate limit in cache)
**Permission Checks:** None
**Loading/Error States:** 429 response with retry-after
**Compatibility:** No breaking changes
**Migration:** None
**Accessibility:** Rate limit message bilingual
**Offline:** N/A

**Implementation:**

1. Create `app/Http/Middleware/ThrottlePasswordReset.php`
2. Apply to password reset request route
3. Configure: 3 attempts per hour, keyed by IP
4. Add rate limit headers
5. Log rate limit hits

**Tests:**

- 3 reset requests from same IP → 429 on 4th
- Different IPs → independent limits
- Valid reset request resets counter
- Rate limit headers present

**Verification:**

```bash
php artisan test --filter=ThrottlePasswordReset
```

---

## Vertical Slice 7: CI DAST on PRs (LOW)

**Objective:** Run OWASP ZAP scans on pull requests

**Actor:** Developer
**Preconditions:** security.yml exists, ZAP configured
**Action:** Create pull request
**Expected Result:** ZAP scan runs on PR, results posted as comment
**Data Changes:** None
**Permission Checks:** GITHUB_TOKEN permissions
**Loading/Error States:** Scan failure doesn't block merge
**Compatibility:** No breaking changes
**Migration:** None
**Accessibility:** N/A
**Offline:** N/A

**Implementation:**

1. Update `.github/workflows/security.yml` to run ZAP on PRs
2. Configure ZAP to scan only changed endpoints
3. Post scan results as PR comment
4. Make scan non-blocking (warning only)
5. Add scan artifacts to GitHub Actions

**Tests:**

- ZAP runs on PR
- Results posted as comment
- Scan failure doesn't block merge
- Artifacts uploaded

**Verification:**

```bash
# In PR context
gh workflow run security.yml --ref <pr-branch>
```

---

## Vertical Slice 8: Session Security (LOW)

**Objective:** Harden session configuration

**Actor:** System administrator
**Preconditions:** Session configuration exists
**Action:** Update session config
**Expected Result:** SESSION_SECURE_COOKIE=true, SESSION_SAMESITE=strict
**Data Changes:** None
**Permission Checks:** N/A
**Loading/Error States:** None
**Compatibility:** Must work with PWA
**Migration:** None
**Accessibility:** N/A
**Offline:** N/A

**Implementation:**

1. Update `.env.example` with SESSION_SECURE_COOKIE=true
2. Update `config/session.php` to use `strict` same-site
3. Add session configuration documentation
4. Test with PWA functionality
5. Monitor session issues

**Tests:**

- Session cookie has Secure flag
- Session cookie has SameSite=Strict
- PWA functionality preserved
- Session regeneration works

**Verification:**

```bash
php artisan test --filter=SessionSecurity
```

---

## Architecture Decisions

### 1. MFA Implementation Approach

**Decision:** Start with admin panel only, TOTP-based (Google Authenticator compatible)
**Alternatives:** SMS-based (rejected - cost, SIM swap risk), Hardware keys (rejected - cost, complexity)
**Tradeoffs:** TOTP is free, secure, but requires app installation
**Reversibility:** High - can disable per user
**Owner:** Security team

### 2. CSP Nonce Strategy

**Decision:** Use per-request nonces where Livewire 4 supports it, keep unsafe-inline for styles
**Alternatives:** Hash-based CSP (rejected - complex), Remove all inline (rejected - breaks Livewire)
**Tradeoffs:** Nonces are secure but require Livewire 4 support
**Reversibility:** High - can revert to unsafe-inline
**Owner:** Frontend team

### 3. Rate Limiting Storage

**Decision:** Use Laravel cache (database/Redis) for rate limits
**Alternatives:** Session-based (rejected - not scalable), IP-only (rejected - too broad)
**Tradeoffs:** Database is persistent but slower, Redis is fast but requires setup
**Reversibility:** High - can change storage driver
**Owner:** Backend team

---

## Milestones

### Milestone 1: Critical Security Fixes (Week 1)

- Vertical Slice 1: Login Rate Limiting
- Vertical Slice 2: Sanctum Token Expiration
- **Gate:** All tests pass, security audit clean

### Milestone 2: Medium Security Fixes (Week 2)

- Vertical Slice 3: CSP Hardening
- Vertical Slice 4: Device UUID Security
- Vertical Slice 5: Trusted Proxy Configuration
- Vertical Slice 6: Password Reset Rate Limiting
- **Gate:** All tests pass, CSP violations minimal

### Milestone 3: Low Priority Fixes (Week 3)

- Vertical Slice 7: CI DAST on PRs
- Vertical Slice 8: Session Security
- **Gate:** CI pipeline complete, session config production-ready

### Milestone 4: Documentation & Training (Week 4)

- Update SECURITY.md with all changes
- Create security runbook
- Train team on new security controls
- **Gate:** Documentation complete, team trained

---

## Critical Path

1. Login Rate Limiting → Blocks brute force attacks
2. Sanctum Token Expiration → Prevents token leakage
3. CSP Hardening → Reduces XSS risk
4. All other fixes build on these foundations

## Approval Gates

1. After Milestone 1: Security team review
2. After Milestone 2: Penetration testing
3. After Milestone 3: Production deployment approval
4. After Milestone 4: Security audit sign-off

## Risks

### Product Risks

- **MFA adoption:** Users may resist MFA → Mitigate with clear documentation, optional for reps initially
- **CSP breaking changes:** Livewire may break → Mitigate with thorough testing, rollback plan

### Security Risks

- **Rate limiting bypass:** Sophisticated attacks may bypass → Mitigate with multiple layers (IP, user, email)
- **Token leakage:** Short-lived tokens still risky → Mitigate with secure storage, rotation

### Privacy Risks

- **Device fingerprinting:** May collect too much data → Mitigate with minimal data collection
- **Rate limit logging:** May log sensitive IPs → Mitigate with log rotation, anonymization

### Migration Risks

- **Token expiration:** Existing tokens unaffected → No migration needed
- **CSP changes:** May break styles → Test thoroughly, staged rollout

### Operational Risks

- **Rate limiting storage:** Database load → Monitor performance, consider Redis
- **MFA setup:** User support burden → Create clear documentation, support process

### Delivery Risks

- **Livewire 4 nonce support:** May not be available → Fallback to unsafe-inline
- **Testing complexity:** Security tests may be flaky → Use deterministic test data

---

## Documents Written

1. `SECURITY_HARDENING_PLAN.md` - This document
2. `SECURITY.md` - Updated with new controls
3. `.env.example` - Updated with new variables
4. `CHANGELOG.md` - Security fixes documented

## Next Vertical Slice

**Vertical Slice 1: Login Rate Limiting** - Highest priority, blocks brute force attacks

## Recommended Next Skill

**v-implementation-strategist** - For detailed implementation guidance on each vertical slice

---

## Result Schema

```yaml
plan_result:
  scope:
    - "Fix 10 identified security gaps"
    - "Implement login rate limiting"
    - "Set Sanctum token expiration"
    - "Harden CSP configuration"
    - "Secure device identification"
    - "Restrict trusted proxies"
    - "Add password reset rate limiting"
    - "Enable CI DAST on PRs"
    - "Harden session configuration"
  non_goals:
    - "Full MFA rollout (admin panel only)"
    - "Complete CSP migration (requires Livewire 4)"
    - "Fix all 38 owner decisions"
    - "Address PR-003 through PR-031"
  acceptance_criteria_count: 40
  architecture_decisions:
    - "MFA: TOTP-based, admin panel only"
    - "CSP: Nonce-based where possible"
    - "Rate limiting: Database/Redis storage"
  milestones:
    - "Week 1: Critical security fixes"
    - "Week 2: Medium security fixes"
    - "Week 3: Low priority fixes"
    - "Week 4: Documentation & training"
  critical_path:
    - "Login Rate Limiting"
    - "Sanctum Token Expiration"
    - "CSP Hardening"
  approval_gates:
    - "Security team review after Week 1"
    - "Penetration testing after Week 2"
    - "Production deployment approval after Week 3"
    - "Security audit sign-off after Week 4"
  risks:
    - "MFA adoption resistance"
    - "CSP breaking changes"
    - "Rate limiting bypass"
    - "Token leakage"
    - "Device fingerprinting privacy"
    - "Rate limit storage performance"
    - "Livewire 4 nonce support"
  documents_written:
    - "SECURITY_HARDENING_PLAN.md"
    - "SECURITY.md (updated)"
    - ".env.example (updated)"
    - "CHANGELOG.md (updated)"
  next_vertical_slice: "Vertical Slice 1: Login Rate Limiting"
  recommended_next_skill: v-implementation-strategist
```
