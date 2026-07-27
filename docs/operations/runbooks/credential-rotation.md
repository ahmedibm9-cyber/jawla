# Runbook: Credential rotation

## When

- After a suspected compromise
- On a scheduled rotation (quarterly for production secrets)
- When a team member with access leaves

## Steps

1. Generate new secret (e.g., `php artisan key:generate` for APP_KEY).
2. Update in hosting environment (Railway dashboard / Forge .env).
3. Restart the application.
4. Verify: login works, API calls succeed, Sentry receives events.
5. Revoke old secret if provider supports it.
6. Record rotation date in security log.
