# Incidents

## Who to Call

| Priority            | Contact                  | Phone   | When           |
| ------------------- | ------------------------ | ------- | -------------- |
| P1 — App down       | Ahmed (Developer)        | [PHONE] | Immediately    |
| P2 — Feature broken | Ahmed (Developer)        | [PHONE] | Within 1 hour  |
| P3 — Question/usage | WhatsApp "Jawla Support" | —       | Business hours |

## What to Check First

1. **Railway dashboard** — is the service "Active"?
2. **`/health`** — does it return 200?
3. **Railway logs** — any errors in the last 5 minutes?
4. **Database** — is PostgreSQL responding?

## How to Rollback

```bash
# Via Railway CLI
railway rollback

# Via Railway dashboard
# Deployments → click 3 dots on previous deployment → Roll back
```

## Escalation

If rollback doesn't fix it:

1. Check Sentry for error details
2. Check Railway database backups (Settings → Backups)
3. Call Ahmed
