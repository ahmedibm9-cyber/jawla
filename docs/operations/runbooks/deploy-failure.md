# Runbook: Deploy failure

## Symptom

Deploy script exits non-zero or health check fails after deploy.

## Immediate actions

1. Check deploy logs: `scripts/deploy.sh` output or Railway deploy logs.
2. If migration failed: `php artisan migrate:status` to see which batch failed.
3. If health check failed: `curl -sf http://localhost/up` and check PHP-FPM/Nginx.

## Rollback

1. `git log --oneline -5` to find last good commit.
2. `git checkout <good-commit> -- .`
3. Run `scripts/deploy.sh` again.
4. Verify health check passes.

## Prevention

- Run `make verify` before pushing.
- Never push migrations without testing rollback path.
