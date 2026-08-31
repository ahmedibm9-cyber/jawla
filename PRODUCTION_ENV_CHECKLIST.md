# Production Environment Checklist

Set these in Railway dashboard before go-live.

## Critical (Must Have)

| Variable                | Value                        | Set By    | Verified |
| ----------------------- | ---------------------------- | --------- | -------- |
| `APP_KEY`               | base64:(fresh 32-byte key)   | Developer | [ ]      |
| `APP_DEBUG`             | false                        | Developer | [ ]      |
| `APP_ENV`               | production                   | Developer | [ ]      |
| `APP_URL`               | https://jawla.up.railway.app | Developer | [ ]      |
| `SESSION_ENCRYPT`       | true                         | Developer | [ ]      |
| `SESSION_DRIVER`        | redis                        | Developer | [ ]      |
| `SESSION_SECURE_COOKIE` | true                         | Developer | [ ]      |
| `LOG_LEVEL`             | warning                      | Developer | [ ]      |
| `DB_CONNECTION`         | pgsql                        | Railway   | [x]      |
| `DB_HOST`               | (Railway managed)            | Railway   | [x]      |
| `DB_DATABASE`           | (Railway managed)            | Railway   | [x]      |
| `DB_USERNAME`           | (Railway managed)            | Railway   | [x]      |
| `DB_PASSWORD`           | (Railway managed)            | Railway   | [x]      |

## Important (Should Have)

| Variable                    | Value                   | Set By    | Verified |
| --------------------------- | ----------------------- | --------- | -------- |
| `CACHE_DRIVER`              | redis                   | Developer | [ ]      |
| `QUEUE_CONNECTION`          | redis                   | Developer | [ ]      |
| `SESSION_LIFETIME`          | 960 (16 hours for reps) | Developer | [ ]      |
| `SANCTUM_TOKEN_EXPIRATION`  | 480 (8 hours)           | Developer | [ ]      |
| `SENTRY_DSN`                | (from Sentry dashboard) | Developer | [ ]      |
| `SENTRY_TRACES_SAMPLE_RATE` | 0.1 (10%)               | Developer | [ ]      |

## ETA (Deferred to Week 2)

| Variable            | Value                             | Set By    | Verified |
| ------------------- | --------------------------------- | --------- | -------- |
| `ETA_ENABLED`       | false (stub mode for launch week) | Developer | [ ]      |
| `ETA_API_BASE_URL`  | (from ETA credentials)            | Client    | [ ]      |
| `ETA_ID_BASE_URL`   | (from ETA credentials)            | Client    | [ ]      |
| `ETA_CLIENT_ID`     | (from ETA credentials)            | Client    | [ ]      |
| `ETA_CLIENT_SECRET` | (from ETA credentials)            | Client    | [ ]      |
| `ETA_TAXPAYER_RIN`  | (from ETA credentials)            | Client    | [ ]      |

## Storage

| Variable                | Value                 | Set By    | Verified |
| ----------------------- | --------------------- | --------- | -------- |
| `PHOTO_DISK`            | s3                    | Developer | [ ]      |
| `AWS_ACCESS_KEY_ID`     | (from Railway bucket) | Developer | [ ]      |
| `AWS_SECRET_ACCESS_KEY` | (from Railway bucket) | Developer | [ ]      |
| `AWS_DEFAULT_REGION`    | ams                   | Developer | [ ]      |
| `AWS_BUCKET`            | jawla-photos          | Developer | [ ]      |

## Verification Steps

After setting all variables:

1. [ ] Deploy to Railway
2. [ ] Check `/health` returns 200
3. [ ] Log in as super admin at `/admin`
4. [ ] Log in as rep at `/app`
5. [ ] Verify `config('session.encrypt')` returns `true` in tinker
6. [ ] Verify `config('app.debug')` returns `false` in tinker
7. [ ] Check Sentry dashboard for test error
8. [ ] Run `php artisan route:list` — no errors
