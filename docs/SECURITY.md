# Security

## Secrets

Only `.env`. Never in code, blade, JS, logs, or client responses. Rotate
on compromise via Forge.

## Auth & sessions

- argon2id password hashing (`config/hashing.php`).
- Sessions: httpOnly + secure (prod) + sameSite=lax + regenerate on login.
- Admin session lifetime 12h; rep 16h.
- Login throttle 5/min per IP + email (via `throttle:login` middleware).
- Sanctum API tokens expire after 24 hours (configurable via `SANCTUM_TOKEN_EXPIRATION`).

## Transport & headers

- Force HTTPS, HSTS, X-Content-Type-Options, X-Frame-Options DENY,
  Referrer-Policy, and a CSP allowing self + Vite build output.

## Input & output

- Server-side validation on every write (Form Requests / Livewire rules).
- Whitelist `$fillable`. Never `$request->all()` into a model.
- Blade escapes by default; never `{!! !!}` on user content.
- Eloquent / query builder only — no raw SQL string concatenation.

## Uploads

- Images only; validated mime + size ≤ 2 MB.
- Stored outside webroot; served through signed routes.

## Automation

- `composer audit` + `npm audit --audit-level=high` on every push.
- OWASP ZAP baseline weekly against staging.
- Manual Burp Suite pass on auth + invoice + IDOR before go-live.
- Dependabot + CodeQL enabled at repo level.

## Threat model

### Sensitive assets

- Customer PII (names, phone numbers, addresses)
- Financial data (invoices, payments, balances)
- Van stock quantities (inventory accuracy)
- User credentials and sessions
- Company configuration (VAT rates, pricing)

### Trust boundaries

1. **Browser → Laravel**: Session-cookie auth, CSRF tokens, server-side policies.
2. **Laravel → PostgreSQL**: Eloquent ORM only, parameterized queries, no raw SQL.
3. **Laravel → External services**: Sentry (error), S3 (backup), ETA (invoicing).
   Failed external dependency must not make a financial mutation appear complete.

### Entry points

- `/admin/*` — Filament admin panel (requires auth + role)
- `/app/*` — Rep PWA (requires auth + rep role)
- `/app/sync` — Offline sync endpoint (requires auth, idempotent)
- `/api/*` — API routes (if any, rate-limited)

### Abuse cases

- IDOR: rep accessing another company's data → mitigated by company scope
- Stock manipulation: direct DB update bypassing StockService → mitigated by
  `StockService` being the only write path, audited via `stock_movements`
- Session hijacking: mitigated by httpOnly + secure + sameSite cookies,
  session regeneration on login
- CSRF on financial actions: mitigated by Laravel CSRF tokens + confirmation modals
- Invoice tampering: numbers are sequential, server-generated, immutable

### Mitigations

- Company scope on all queries (spatie/laravel-permission + global scope)
- `$fillable` whitelist on all models
- Rate limiting on login and POST routes
- OWASP ZAP baseline scan weekly
- `composer audit` + `npm audit` on every push
- Dependabot for dependency updates

### Residual risks

- Offline sync: rep could theoretically queue conflicting operations;
  idempotency keys and server-side conflict detection mitigate this
- ETA integration: external service availability is outside our control

## Secrets policy

### What is a secret

- Database credentials (`DB_PASSWORD`, `DATABASE_URL`)
- Application key (`APP_KEY`)
- Cache/session/queue credentials (`REDIS_URL`, `REDIS_PASSWORD`)
- API keys (Sentry DSN, S3 keys, ETA credentials)
- Any token that grants access to a system

### Where secrets live

- **Production**: Set in Railway dashboard (or Forge .env). Never in code.
- **Development**: `.env` file, gitignored. Use `.env.example` as template.
- **CI/CD**: GitHub Actions secrets. Never hardcoded in workflow files.

### Rotation

- Rotate immediately on suspected compromise.
- Rotate quarterly for production database and Redis credentials.
- Rotate APP_KEY only if compromise is suspected (invalidates all sessions).

### Access control

- Only team members with production access may view production secrets.
- Backup credentials are separate from application credentials.
- Never share secrets via Slack, email, or chat. Use the hosting dashboard.

### What never gets committed

- `.env` files
- API keys or tokens in source code
- Private keys (`.pem`, `.p12`, `.pfx`)
- Database connection strings with passwords
- Backup encryption keys
- Agent chat transcripts containing secrets
