# Monitoring & Alerting

## Health Endpoint

- **URL:** `GET /health`
- **Response:** `{"status": "ok|degraded", "db": "ok|failed", "cache": "ok|failed"}`
- **Codes:** 200 = healthy, 503 = degraded
- **Cache-Control:** no-store, private

## Uptime Monitoring

`.github/workflows/production-health.yml` provides a repository-owned baseline:

- runs every five minutes and on manual dispatch;
- retries `/health` three times;
- requires `status`, `db`, and `cache` to equal `ok`;
- opens or updates a labeled P1 GitHub issue on failure;
- closes the open incident issue after recovery.

This monitor becomes operational only after the workflow is pushed, its
`PRODUCTION_HEALTH_URL` repository variable points to the production `/health`
URL, GitHub Actions is enabled, and a named owner subscribes to P1 issues.

For a 60-second independent signal, configure an external monitor
(UptimeRobot, BetterStack, or healthchecks.io) in addition to the GitHub
baseline.

### Recommended setup (UptimeRobot)

1. Create account at uptimerobot.com
2. Add HTTP(S) monitor:
   - URL: `https://your-domain.com/health`
   - Interval: 60 seconds
   - Alert contact: engineering lead phone + Slack/email
3. Configure alert thresholds:
   - Down after: 2 consecutive failures
   - Recovery after: 1 successful check

### Alternative: healthchecks.io

1. Create check with period 60s, grace 120s
2. curl `https://hc-ping.com/<your-uuid>` from a cron or Railway cron service
3. Configure notification channels (email, SMS, Slack, PagerDuty)

## Sentry Alert Routing

Sentry is configured in `config/sentry.php` with `SentryScrubber` masking sensitive data.

### Alert rules to configure in Sentry dashboard

1. **P1 — New error in production:** Alert on first occurrence of any `error`-level event in `production` environment. Route to: engineering Slack channel + on-call phone.
2. **P2 — Error frequency spike:** Alert when error count > 10 in 1 hour. Route to: engineering Slack channel.
3. **P3 — Performance regression:** Alert when p95 response time > 3s for 5 minutes. Route to: engineering Slack channel.

### Alert channels

Configure in Sentry → Settings → Alerts → Notification Channels:

- **Slack:** Add workspace webhook, create `#jawla-alerts` channel
- **Email:** Add on-call engineering email
- **PagerDuty:** (optional) Add service for P1 incidents

## Sync Conflict Monitoring

The offline sync system tracks conflicts via `sync_receipts` table. Monitor:

- Query: `SELECT COUNT(*) FROM sync_receipts WHERE response IS NULL AND created_at > NOW() - INTERVAL '1 hour'`
- Alert threshold: > 0 ambiguous receipts (these need support review)

## Key Metrics to Watch

- `/health` response time and status
- Sentry error rate (new errors per hour)
- Sync conflict count
- Database connection pool usage
- Railway deployment status
