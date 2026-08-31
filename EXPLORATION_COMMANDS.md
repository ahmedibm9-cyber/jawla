# Project Commands

## Status Definitions

- **Verified** — executed successfully during exploration.
- **Declared** — found in documentation or configuration, not executed.
- **Inferred** — likely command based on tooling, not explicitly declared.
- **Blocked** — attempted or considered but unavailable or unsafe.

## Prerequisites

| Requirement | Version or details                                                         | Evidence         | Status   |
| ----------- | -------------------------------------------------------------------------- | ---------------- | -------- |
| PHP         | 8.3+ with extensions (pdo_pgsql, gd, mbstring, zip, bcmath, intl, opcache) | composer.json:11 | Declared |
| Node.js     | 22+                                                                        | Dockerfile:52    | Declared |
| PostgreSQL  | 16                                                                         | README.md:14     | Declared |
| Composer    | 2                                                                          | Dockerfile:1     | Declared |
| npm         | Included with Node.js                                                      | package.json     | Declared |

## Setup

| Command                                  | Purpose                                      | Status   | Side effects                                 | Evidence or result |
| ---------------------------------------- | -------------------------------------------- | -------- | -------------------------------------------- | ------------------ |
| `make setup`                             | Full project setup (install, migrate, build) | Declared | Creates .env, runs migrations, builds assets | Makefile:3-9       |
| `composer install`                       | Install PHP dependencies                     | Declared | Creates vendor/ directory                    | composer.json:67   |
| `npm install`                            | Install JS dependencies                      | Declared | Creates node_modules/ directory              | package.json:6     |
| `php artisan key:generate`               | Generate APP_KEY                             | Declared | Updates .env                                 | Laravel convention |
| `php artisan migrate`                    | Run database migrations                      | Declared | Creates/updates tables                       | Makefile:53-54     |
| `php artisan db:seed --class=DemoSeeder` | Seed demo data                               | Declared | Populates database with test data            | Makefile:56-57     |

## Run

| Command                    | Process started                  | Status   | Network or external dependencies | Notes            |
| -------------------------- | -------------------------------- | -------- | -------------------------------- | ---------------- |
| `make dev`                 | PHP server + queue + logs + Vite | Declared | PostgreSQL, Redis                | Makefile:11-17   |
| `php artisan serve`        | PHP development server           | Declared | None                             | Part of make dev |
| `php artisan queue:listen` | Queue worker                     | Declared | Database                         | Part of make dev |
| `php artisan pail`         | Log viewer                       | Declared | None                             | Part of make dev |
| `npm run dev`              | Vite dev server                  | Declared | None                             | Part of make dev |

## Test

| Command                                     | Scope                       | Status   | Result                          | Notes          |
| ------------------------------------------- | --------------------------- | -------- | ------------------------------- | -------------- |
| `make test`                                 | Unit + Feature tests (Pest) | Declared | 975+ tests                      | Makefile:30-31 |
| `php artisan test --testsuite=Unit,Feature` | Same as make test           | Declared | —                               | Makefile:31    |
| `make test-e2e`                             | Browser tests (Playwright)  | Declared | Windows limitation noted        | Makefile:39-45 |
| `make test-offline`                         | Offline safety tests        | Declared | Node.js test runner             | Makefile:33-34 |
| `make test-perf`                            | Performance tests (k6)      | Declared | k6 load testing                 | Makefile:36-37 |
| `make verify`                               | Full verification           | Declared | lint + typecheck + test + build | Makefile:66    |

## Lint, Format, and Type Check

| Command                  | Purpose                           | Status   | Result              | Notes          |
| ------------------------ | --------------------------------- | -------- | ------------------- | -------------- |
| `make lint`              | PHP code style (Pint)             | Declared | —                   | Makefile:19-20 |
| `vendor/bin/pint --test` | Check code style                  | Declared | —                   | Makefile:20    |
| `make typecheck`         | Static analysis (PHPStan level 0) | Declared | —                   | Makefile:22-23 |
| `make typecheck-strict`  | Strict analysis (PHPStan level 6) | Declared | Legacy debt backlog | Makefile:27-28 |

## Build and Package

| Command                   | Artifact                     | Status   | Result | Notes               |
| ------------------------- | ---------------------------- | -------- | ------ | ------------------- |
| `make build`              | Vite assets in public/build/ | Declared | —      | Makefile:50-51      |
| `npm run build`           | Same as make build           | Declared | —      | package.json:8      |
| `php artisan optimize`    | Laravel optimization cache   | Declared | —      | composer.json:61-66 |
| `php artisan view:cache`  | Compiled Blade views         | Declared | —      | composer.json:63    |
| `php artisan icons:cache` | Cached icons                 | Declared | —      | composer.json:64    |

## Database and Data

| Command                                  | Purpose                      | Status   | Risk or side effect                          | Safe environment          |
| ---------------------------------------- | ---------------------------- | -------- | -------------------------------------------- | ------------------------- |
| `php artisan migrate`                    | Run pending migrations       | Declared | Schema changes ( irreversible after release) | Dev, staging              |
| `php artisan migrate --force`            | Run migrations in production | Declared | Same as above                                | Production (with caution) |
| `php artisan db:seed --class=DemoSeeder` | Seed demo data               | Declared | Populates database                           | Dev only                  |
| `php artisan db:wipe`                    | Drop all tables              | Declared | Data loss                                    | Dev only                  |

## Deployment and Release

| Command or pipeline         | Target            | Status   | Trigger          | Notes                          |
| --------------------------- | ----------------- | -------- | ---------------- | ------------------------------ |
| Railway deploy              | Production        | Declared | Git push to main | railway.toml:1-8               |
| `docker/start-container.sh` | Container startup | Declared | Railway deploy   | Dockerfile:130                 |
| GitHub Actions CI           | Staging           | Declared | Pull request     | lint → test → security → build |
| OWASP ZAP scan              | Staging           | Declared | Weekly           | docs/SECURITY.md:37            |
