# Security policy

## Reporting
Email security concerns privately to the repository owner. Do not open a
public issue. We will acknowledge within 72 hours.

## Supported versions
Only `main` is supported. Older branches receive no security updates.

## Baseline (see `docs/SECURITY.md` for full detail)
- Secrets only in `.env`, never in code or client responses.
- argon2id password hashing; TLS 1.2+ enforced; AES-256-GCM at rest.
- CSRF, XSS-safe Blade escaping, whitelisted `$fillable`, parameterized
  queries only (Eloquent/query builder).
- OWASP ZAP baseline scan runs weekly in CI (`.github/workflows/security.yml`).
- `composer audit` and `npm audit` on every push (`.github/workflows/ci.yml`).
- Dependabot + CodeQL enabled at repo level.
