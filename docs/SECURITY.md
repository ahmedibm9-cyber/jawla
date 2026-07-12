# Security

## Secrets
Only `.env`. Never in code, blade, JS, logs, or client responses. Rotate
on compromise via Forge.

## Auth & sessions
- argon2id password hashing (`config/hashing.php`).
- Sessions: httpOnly + secure (prod) + sameSite=lax + regenerate on login.
- Admin session lifetime 12h; rep 16h.
- Login throttle 5/min per IP + email with lockout backoff.

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
