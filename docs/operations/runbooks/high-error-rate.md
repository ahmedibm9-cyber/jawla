# Runbook: High error rate

## Symptom

Sentry shows spike in errors, or monitoring alerts fire.

## Immediate actions

1. Check Sentry for the error group and stack trace.
2. Check Railway metrics: CPU, memory, response time.
3. Check recent deploys: did a new release go out?
4. Check database: connection pool exhaustion? Slow queries?

## Triage

- If caused by a deploy: rollback (see deploy-failure runbook).
- If database: check `SHOW processlist` / `pg_stat_activity`.
- If external service (Sentry, S3, ETA): check status pages, disable if degraded.

## Communication

- Notify team in incident channel.
- If customer-facing: update status page.
