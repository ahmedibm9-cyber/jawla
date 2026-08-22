# COMMANDS

## Setup and Development

| Command            | Purpose                  | Side Effects                                                    | Status   |
| ------------------ | ------------------------ | --------------------------------------------------------------- | -------- |
| `make setup`       | Initial project setup    | Installs deps, generates key, runs migrations, builds assets    | Declared |
| `make dev`         | Start development server | Runs 4 processes: PHP server, queue worker, Pail logs, Vite dev | Declared |
| `composer install` | Install PHP dependencies | Modifies `vendor/` directory                                    | Declared |
| `npm install`      | Install JS dependencies  | Modifies `node_modules/` directory                              | Declared |
| `npm run dev`      | Start Vite dev server    | Watches and compiles assets                                     | Declared |
| `npm run build`    | Build production assets  | Writes to `public/build/`                                       | Declared |

## Code Quality

| Command                      | Purpose                    | Side Effects         | Status   |
| ---------------------------- | -------------------------- | -------------------- | -------- |
| `make lint`                  | Check PHP code style       | No changes (dry run) | Declared |
| `make typecheck`             | PHPStan analysis (level 0) | No changes           | Declared |
| `make typecheck-strict`      | PHPStan analysis (level 6) | No changes           | Declared |
| `vendor/bin/pint --test`     | Laravel Pint dry run       | No changes           | Declared |
| `vendor/bin/phpstan analyse` | Static analysis            | No changes           | Declared |

## Testing

| Command             | Purpose                     | Side Effects          | Status   |
| ------------------- | --------------------------- | --------------------- | -------- |
| `make test`         | Run Unit + Feature tests    | Creates test database | Declared |
| `make test-e2e`     | Run browser tests           | Requires Playwright   | Declared |
| `make test-offline` | Run JS offline safety tests | No side effects       | Declared |
| `make test-perf`    | Run performance tests       | No side effects       | Declared |
| `make verify`       | Full verification suite     | Runs all checks       | Declared |

### Test Database

- Uses `database/database.sqlite` for testing
- Tests use `RefreshDatabase` trait
- Isolated test runs

## Database Operations

| Command                        | Purpose                    | Side Effects             | Status   |
| ------------------------------ | -------------------------- | ------------------------ | -------- |
| `make migrate`                 | Run pending migrations     | Modifies database schema | Declared |
| `make seed`                    | Seed demo data             | Inserts test data        | Declared |
| `php artisan migrate:status`   | Check migration status     | No changes               | Declared |
| `php artisan migrate:rollback` | Rollback last migration    | Reverts schema changes   | Declared |
| `php artisan migrate:fresh`    | Drop and recreate database | Destructive              | Declared |

## Production Operations

| Command                                       | Purpose              | Side Effects                 | Status   |
| --------------------------------------------- | -------------------- | ---------------------------- | -------- |
| `make smoke`                                  | Quick health check   | Caches config and views      | Declared |
| `php artisan config:cache`                    | Cache configuration  | Writes to `bootstrap/cache/` | Declared |
| `php artisan view:cache`                      | Cache compiled views | Writes to `storage/views/`   | Declared |
| `php artisan route:list --columns=method,uri` | List routes          | No changes                   | Declared |
| `php artisan optimize`                        | Optimize application | Caches config, views, events | Declared |

## Deployment

| Command              | Purpose                      | Side Effects                   | Status   |
| -------------------- | ---------------------------- | ------------------------------ | -------- |
| `railway deploy`     | Deploy to Railway            | Triggers production deployment | Declared |
| `scripts/deploy.sh`  | Deployment script            | Full deployment process        | Declared |
| `scripts/backup.sh`  | Database backup              | Creates backup file            | Declared |
| `scripts/restore.sh` | Database restore             | Restores from backup           | Declared |
| `scripts/verify.sh`  | Post-deployment verification | Runs health checks             | Declared |

## Development Utilities

| Command                    | Purpose                     | Side Effects              | Status   |
| -------------------------- | --------------------------- | ------------------------- | -------- |
| `php artisan tinker`       | Interactive REPL            | No changes                | Declared |
| `php artisan pail`         | Real-time log viewer        | No changes                | Declared |
| `php artisan queue:listen` | Process queue jobs          | Processes background jobs | Declared |
| `php artisan queue:work`   | Process queue jobs (daemon) | Processes background jobs | Declared |

## Asset Management

| Command                         | Purpose                 | Side Effects                 | Status   |
| ------------------------------- | ----------------------- | ---------------------------- | -------- |
| `npm run build`                 | Build production assets | Writes to `public/build/`    | Declared |
| `php artisan icons:cache`       | Cache icon SVGs         | Writes to `bootstrap/cache/` | Declared |
| `php artisan filament:optimize` | Optimize Filament       | Caches Filament components   | Declared |

## Security Operations

| Command                        | Purpose                   | Side Effects   | Status   |
| ------------------------------ | ------------------------- | -------------- | -------- |
| `php artisan key:generate`     | Generate application key  | Updates `.env` | Declared |
| `composer audit`               | Check for vulnerabilities | No changes     | Declared |
| `npm audit --audit-level=high` | Check JS vulnerabilities  | No changes     | Declared |

## Monitoring and Debugging

| Command                        | Purpose                 | Side Effects     | Status   |
| ------------------------------ | ----------------------- | ---------------- | -------- |
| `php artisan pail --timeout=0` | Real-time log streaming | No changes       | Declared |
| `sentry:test`                  | Test Sentry integration | Sends test event | Declared |
| `php artisan route:list`       | List all routes         | No changes       | Declared |

## Notes

- All commands are from `Makefile` or `composer.json` scripts
- Production commands should be run in deployment context
- Test commands create isolated test databases
- Destructive commands (migrate:fresh) require explicit confirmation
- Browser tests have Windows limitation (pest-plugin-browser bug #1517)
