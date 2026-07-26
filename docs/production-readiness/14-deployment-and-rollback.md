# Deployment and Rollback

## Verdict

**FAIL.** The source deployment path is unsafe for production financial data.

## Critical pre-deploy defect

`railway.toml` runs:

1. forced migrations;
2. a super-admin seeding command using `superadmin@jawla.test` and `password`;
3. `DemoSeeder --force`, which can create demo users and randomized financial/stock data.

On an empty database, the super-admin command runs before a company exists and can fail. On an existing environment, known credentials and synthetic data may be introduced. See PR-005.

## Release-chain gaps

- Deployment targets mutable `master`, not an immutable promoted artifact.
- Migration runs before promotion without a demonstrated independent backup gate.
- The deploy script exits after health failure but does not perform rollback.
- `/up` is static and does not prove PostgreSQL, Redis, object storage, queues, schema, or business readiness.
- Docker runtime copies committed `public/build` instead of building/promoting the CI-verified artifact.
- E2E is `continue-on-error`.
- ZAP is advisory and forced successful; local target is not staging.
- Larastan/PHPStan is absent.
- Repository evidence does not prove branch protection or that Railway waits for required checks.
- Playwright installation uses `latest` in workflow, reducing reproducibility.

## Required release design

1. Build once; sign/hash and promote the exact image/artifact.
2. Block promotion on formatting, static analysis, audits, full PostgreSQL tests, browser critical paths, and security severity policy.
3. Eliminate all production demo/known-credential seeders.
4. Use expand/contract schema changes compatible with old and new replicas/clients.
5. Take and verify the required recovery point before risky data migrations.
6. Run dependency-aware readiness checks and synthetic financial smoke tests.
7. Promote progressively and observe error/business-invariant metrics.
8. Support application rollback without assuming destructive database rollback.
9. Retain artifact, schema, configuration, migration, approver, and evidence manifest.

## Rollback drill

The drill must exercise:

- failed readiness after migration;
- previous application against forward-compatible schema;
- new/old service-worker clients and queued operations;
- no duplicated or lost money/stock mutations;
- Redis/session continuity;
- restoration/roll-forward if schema/data is incompatible;
- reconciled financial and stock totals afterward.

Until exercised on staging with the exact topology and signed evidence, rollback remains **NOT VERIFIED**.

