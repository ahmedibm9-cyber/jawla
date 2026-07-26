# Security Review

## High-priority conclusions

- **Critical tenant escape:** PR-001.
- **Critical mutable import trust:** PR-002.
- **High stored XSS:** PR-013.
- **High RBAC/privileged-auth gaps:** PR-014.
- **High file/GPS privacy gaps:** PR-015.
- **Medium CSP/telemetry hardening:** PR-022.
- **Critical known production bootstrap credential:** PR-005.

## Authentication and sessions

Positive controls:

- Argon2id hashing.
- Active-user check and session regeneration on inspected login path.
- Production secure/HttpOnly/SameSite cookie settings in configuration.
- Generic POST throttling and a five-attempt Filament limiter.
- CSRF protection in the web stack.

Gaps:

- Filament limiter is keyed by component/method/IP, not the specified normalized email+IP limiter.
- Trusted proxies are configured broadly (`*`), so origin/reverse-proxy protection is security-critical.
- MFA, destructive/financial step-up, absolute or role-specific lifetime, and admin session revocation were not found.
- The pinned revision exposes a state-changing GET logout route.

## Input/output and browser security

- A stored XSS path exists in Leaflet popup HTML.
- CSP contains `unsafe-inline`, `unsafe-eval`, and unpinned third-party script origin.
- Several server entries validate types/lengths, but mutable Livewire state must still be treated as attacker-controlled.
- Inspected PDF values are escaped, and rep PDF routes explicitly constrain company and owner.

## Files and media

The photo disk defaults to public local storage. A production deployment must not rely on an operator remembering to override this. Photos should be private and served through authorized signed/temporary routes. File byte decoding/re-encoding, EXIF stripping, strict size limits, polyglot/malformed detection, COA limits/scanning, and strict signature decoding are incomplete.

## GPS assurance and privacy

Server distance recomputation proves only that client-supplied coordinates fall within a radius. It does not prove physical presence or resistance to mock/stale coordinates. Near-real-time tracking is mounted globally for the rep app and lacks repository evidence of approved lawful basis, notice, precision/retention rule, deletion/export, visible tracking state, or access review.

## Telemetry and logs

Sentry has `send_default_pii=false`, SQL bindings disabled by default, and a custom recursive scrubber for common secrets in selected surfaces. Production DSN, releases, alerts, DPA, retention, outbound sample events, and complete domain-data redaction are unverified. Synthetic events must prove removal of credentials, tax IDs, customer contacts, amounts/balances, and GPS coordinates from request, query, message, breadcrumbs, contexts, tags, user and extra fields.

## Supply chain

- `composer validate`: passed with six unbounded-constraint warnings.
- `composer audit --locked`: no known advisories at audit time.
- `npm audit --package-lock-only --audit-level=high`: zero vulnerabilities at audit time.
- Both lockfiles are tracked.
- CI security scanning is advisory/incomplete; no repository-history secret scan was performed.

## Explicitly unverified

Production environment flags, cookie/header behavior, origin firewall/proxy trust, object-store bucket policy, malware scanning, Sentry payloads/provider controls, secrets in Git history/CI stores, API token expiry policy, branch protection, and external penetration testing.

