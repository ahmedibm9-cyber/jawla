# Jawla deployment

## Environments

| Environment    | URL                                            | Railway                 | Notes                  |
| -------------- | ---------------------------------------------- | ----------------------- | ---------------------- |
| **Production** | `https://jawla-production.up.railway.app`      | service `jawla`         | 2 replicas, SFO region |
| **Staging**    | `https://jawla-staging-staging.up.railway.app` | service `jawla-staging` | 2 replicas, SFO region |

## Release path

Production promotion is defined in `.github/workflows/deploy.yml`:

1. The blocking `CI` workflow must complete successfully on `master`.
2. The exact 40-character commit SHA is checked out and deployed to Railway
   `staging` with the pinned Railway CLI.
3. `GET /health` must report healthy database and cache dependencies.
4. OWASP ZAP runs against staging. Scanner/runtime failures block promotion;
   passive warnings remain evidence for review.
5. The GitHub `production` environment requires a human approval.
6. The same commit SHA is deployed to Railway `production`, followed by the
   same dependency-aware readiness check.
7. Deployment-history JSON and ZAP reports are retained as workflow artifacts.

Do not enable Railway GitHub auto-deploy for the production service alongside
this workflow. It would bypass the staging and approval gates.

## Required GitHub environment configuration

Configure both `staging` and `production` environments:

| Setting              | Scope               | Purpose                                  |
| -------------------- | ------------------- | ---------------------------------------- |
| `RAILWAY_TOKEN`      | secret              | Environment-scoped Railway project token |
| `RAILWAY_PROJECT_ID` | variable            | Target project                           |
| `RAILWAY_SERVICE_ID` | variable            | Web service                              |
| `STAGING_URL`        | staging variable    | Public staging base URL                  |
| `PRODUCTION_URL`     | production variable | Public production base URL               |

Configure required reviewers on the `production` GitHub environment. Repository
configuration alone cannot prove that this external protection is enabled.

The manual rollback workflow additionally requires an account-scoped
`RAILWAY_API_TOKEN` in the production environment.

## Railway config-as-code

`railway.toml` enforces:

- production PHP-FPM/Nginx start command;
- pre-deploy forward migrations and Laravel caches;
- two replicas;
- `/health` readiness with a five-minute timeout;
- restart-on-failure with ten retries.

Environment-specific config (Redis vs database drivers, S3 vs local storage) is
managed in Railway service variables, not `railway.toml` — the TOML file is
shared across environments.

`/up` remains Laravel's lightweight liveness endpoint. Promotion and platform
traffic switching use `/health`, which checks PostgreSQL and the configured
cache store and returns 503 when either dependency is unavailable.

## Container build

The Dockerfile builds frontend assets from `package-lock.json` in a pinned
Node 22 stage, installs production Composer dependencies, enables GD WebP, and
runs PHP-FPM behind Nginx. `.dockerignore` excludes all `.env*` and `storage/*`
runtime material so local credentials and generated demo credentials cannot
enter the image.

## Legacy host deploy

For a non-Railway host, `scripts/deploy.sh` requires an immutable `RELEASE_REF`;
it refuses tracked working-tree changes, checks out the exact commit/tag, runs
the build and forward migrations, and checks `/health`. A failed deploy restores
the previous application release. Database migrations are not automatically
reversed and must remain expand/contract compatible.

```bash
RELEASE_REF=<full-commit-sha-or-signed-tag> \
APP_DIR=/var/www/jawla \
bash scripts/deploy.sh
```

## Release prerequisites

Before promotion:

- `make verify` or `scripts/verify` is green;
- the strict PHPStan level-6 debt report has been reviewed;
- Composer and npm high-severity audits are clean;
- an encrypted pre-deploy backup exists;
- the scratch restore/reconciliation drill is current;
- ETA, privacy/legal, incident ownership, device/offline UAT, accessibility,
  performance, and business sign-off gates have evidence.

Passing CI is necessary but is not authority to process real company data.
