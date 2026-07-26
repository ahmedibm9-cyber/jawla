# Offline PWA and Sync

## Supported behavior observed

- A loaded rep page can place selected operation payloads in IndexedDB.
- The public service-worker cache intentionally avoids authenticated HTML and provides a generic `/offline` fallback.
- The server processes sync operations serially.
- A receipt unique by company and key is created atomically with a successful handler.
- Same-key serial replay tests exist in source.
- Asset budgets passed: JS 50.5 KiB gzip, CSS 22.3 KiB gzip, total 503.1 KiB gzip.

## Critical failure paths

### Ambiguous/double confirmation

The UI does not await durable IndexedDB transaction completion before attempting an offline Livewire request. Every retry gets a new UUID, so server same-key idempotency cannot collapse repeated user intent. See PR-004.

### Irrecoverable discard

Pending, failed, and conflict records can be deleted locally without a financial consequence modal, privilege, server audit, or tombstone. See PR-008.

## Scope mismatch

The service worker pre-caches only `/offline` and `/manifest.json`. It does not support offline launch, reload, authenticated route navigation, or a public/customer/product/stock replica. This privacy-conscious policy is defensible as graceful degradation, but not as a full offline sales application.

The product claim must choose one:

- limited queued write from an already loaded form; or
- full supported offline rep day with local reference data, navigation, conflict rules, privacy controls, and upgrade compatibility.

The second claim is not implemented or verified.

## Protocol and device gaps

- Same key with changed type/payload/user returns the old receipt rather than a conflict.
- No protocol/client version is sent.
- No dependency graph or per-entity causal ordering exists.
- Multi-device duplicate real-world intent is undefined.
- Old clients may be stranded because updates are deferred while queue records remain.
- IndexedDB transaction success is not awaited at `transaction.oncomplete`.
- Quota/abort, browser kill, profile sharing, session expiry, remote device loss, and recovery export are untested.
- Raw financial and signature payloads have no TTL/encryption/recoverable resolution policy.

## Required real-browser matrix

| Scenario | Required assertion |
|---|---|
| Confirm while network disabled | durable record exists before success UI |
| Rapid repeat click | one stable intent and one server mutation |
| Browser killed immediately | operation is either durably queued or clearly not accepted |
| Server commits, response lost | retry returns same result without another mutation |
| Reload/launch offline | behavior matches the advertised support contract |
| Same key, changed content | explicit conflict; no old-result leakage |
| Multiple queued dependent actions | deterministic dependency order or explicit conflict |
| Two devices | documented duplicate-intent behavior |
| Queue conflict/discard | privileged, audited, recoverable resolution |
| Storage quota/abort | explicit failure and recovery path |
| Logout/session expiry/device loss | privacy-preserving purge or remote-revocation policy |
| Service-worker/backend upgrade | old-client compatibility window and non-stranding migration |

Until this matrix passes on supported Arabic/English devices, offline financial operation is a launch blocker.

