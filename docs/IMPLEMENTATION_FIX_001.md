# Implementation Strategy: FIX-001 — GitHub Actions CI Workflow

**Task:** Create `.github/workflows/ci.yml` to run lint, typecheck, test, and build on every push/PR.
**Complexity:** Standard
**Goal:** CI pipeline passes green on `ubuntu-latest` with PostgreSQL service.

---

## 1. Frame the task

| Field           | Value                                                                                                                                             |
| --------------- | ------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Goal**        | Automated CI runs on every push to `main` and every PR, verifying lint + typecheck + unit/feature tests + npm build                               |
| **Context**     | No `.github/workflows/` exists. Tests require PostgreSQL (pgsql driver). PHP 8.3 with 7 extensions. Node 20 for npm build.                        |
| **Constraints** | Must use `ubuntu-latest`. Tests need Postgres service container. `PAO_DISABLE=1` env var required for phpstan and tests. Memory limit 2G for PHP. |
| **Done when**   | `.github/workflows/ci.yml` exists, workflow runs green on push, all steps pass.                                                                   |

---

## 2. Verify implementation context

**Confirmed:**

- No `.github/` directory exists (glob returned empty)
- `Makefile` has all required commands: `lint`, `typecheck`, `test`, `test-offline`, `build`
- `phpunit.xml` configures DB: host=127.0.0.1, port=5432, database=jawla_test, user=postgres, password=postgres
- `tests/_env.php` uses `DB_CONNECTION=pgsql` — cannot use SQLite
- PHP extensions required: bcmath, gd, intl, mbstring, pdo, pdo_pgsql, zip
- `composer.json` requires php ^8.3
- `package.json` uses Node type=module, vite build
- `test-offline` runs `node --test tests/JavaScript/offline-safety.test.js` (no DB needed)

**Likely:**

- Postgres service container with user=postgres, password=postgres, database=jawla_test
- Health check on Postgres before running tests

**Unknown:**

- Whether offline tests pass in CI (they're JS-only, should work)

---

## 3. Complexity gate

**Standard** — multi-file (one new file), cross-layer (PHP + Node + DB service), moderate uncertainty (Postgres service container config, env var propagation).

Full implementation packet below.

---

## 4. Impact map

| Area                       | Status        | Evidence                     |
| -------------------------- | ------------- | ---------------------------- |
| `.github/workflows/ci.yml` | **new file**  | No `.github/` exists         |
| `Makefile`                 | **unchanged** | Already has all commands     |
| `phpunit.xml`              | **unchanged** | Already configured for pgsql |
| `tests/_env.php`           | **unchanged** | Already handles env          |
| `composer.json`            | **unchanged** | Lockfile tracked             |
| `package.json`             | **unchanged** | Lockfile tracked             |
| Production deployment      | **no impact** | CI only, no deploy step      |

---

## 5. Chosen approach

**Summary:** Single workflow file with PHP (Postgres service) + Node jobs. Use `make lint`, `make typecheck`, `make test`, `make test-offline`, `make build` to stay consistent with local dev.

**Rationale:** Reuses existing Makefile commands — no new scripts, no new dependencies. Postgres service container matches phpunit.xml config exactly.

**Invalidated if:** Makefile commands don't work in CI (env var issues, missing extensions).

### Alternatives considered

| Approach                  | Pros                                    | Cons                                | Verdict    |
| ------------------------- | --------------------------------------- | ----------------------------------- | ---------- |
| **A: Makefile commands**  | Consistent with local, minimal new code | Depends on Makefile staying correct | **Chosen** |
| B: Inline commands        | Explicit, no Makefile dependency        | Duplicates logic, drift risk        | Rejected   |
| C: Separate PHP + JS jobs | Parallelism                             | Overkill for this project size      | Rejected   |

---

## 6. Execution sequence

### Step 1: Create directory structure

- **Objective:** `.github/workflows/` directory exists
- **Files:** `.github/workflows/ci.yml` (new)
- **Verification:** `ls .github/workflows/ci.yml`

### Step 2: Write the workflow file

- **Objective:** Complete CI workflow
- **Files:** `.github/workflows/ci.yml`
- **Behavior:**

```yaml
name: CI

on:
  push:
    branches: [main]
  pull_request:
    branches: [main]

jobs:
  ci:
    runs-on: ubuntu-latest

    services:
      postgres:
        image: postgres:16
        env:
          POSTGRES_USER: postgres
          POSTGRES_PASSWORD: postgres
          POSTGRES_DB: jawla_test
        ports:
          - 5432:5432
        options: >-
          --health-cmd pg_isready
          --health-interval 10s
          --health-timeout 5s
          --health-retries 5

    steps:
      - uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: "8.3"
          extensions: bcmath, gd, intl, mbstring, pdo, pdo_pgsql, zip
          coverage: none

      - name: Setup Node
        uses: actions/setup-node@v4
        with:
          node-version: "20"
          cache: "npm"

      - name: Install PHP dependencies
        run: composer install --no-progress --prefer-dist

      - name: Lint (Pint)
        run: make lint

      - name: Typecheck (PHPStan level 0)
        run: make typecheck

      - name: Unit + Feature tests
        run: make test
        env:
          DB_HOST: 127.0.0.1
          DB_PORT: 5432
          DB_DATABASE: jawla_test
          DB_USERNAME: postgres
          DB_PASSWORD: postgres

      - name: Install Node dependencies
        run: npm ci

      - name: Build frontend
        run: make build

      - name: Offline safety tests
        run: make test-offline
```

- **Key details:**
  - Postgres 16 matches Railway's Postgres 18 (close enough for CI)
  - Health check ensures Postgres is ready before PHP steps
  - `PAO_DISABLE=1` is set in Makefile commands (already handled)
  - Memory limit 2G is set in Makefile commands (already handled)
  - Node cache uses `npm` strategy (locks package-lock.json)

- **Verification:** Push to branch, check GitHub Actions tab

### Step 3: Verify locally (dry run)

- **Objective:** Ensure Makefile commands work in sequence
- **Commands:**
  ```bash
  make lint
  make typecheck
  make test
  make build
  make test-offline
  ```
- **Verification:** All exit 0

---

## 7. Test and verification

| Check           | Command                                       | Expected                        |
| --------------- | --------------------------------------------- | ------------------------------- |
| Workflow syntax | `act -l` (if act installed) or push to branch | Lists CI job                    |
| Lint            | `make lint`                                   | Exit 0                          |
| Typecheck       | `make typecheck`                              | Exit 0                          |
| Tests           | `make test`                                   | Exit 0, all pass                |
| Build           | `make build`                                  | Exit 0, `public/build/` created |
| Offline         | `make test-offline`                           | Exit 0                          |
| CI green        | Push to branch, check Actions                 | All steps green                 |

---

## 8. Approval gates

None — this is a low-risk CI configuration change. No production impact.

---

## 9. Rollback and recovery

- **Rollback:** Delete `.github/workflows/ci.yml` or revert the commit
- **Recovery:** N/A — CI is additive, no existing behavior changed

---

## 10. Adaptive checkpoints

| Checkpoint | When                   | What to verify                       |
| ---------- | ---------------------- | ------------------------------------ |
| Pre-edit   | Before creating file   | `.github/` doesn't exist yet         |
| Post-file  | After writing workflow | YAML syntax valid, all steps present |
| Post-push  | After push to branch   | Actions tab shows workflow run       |
| Post-green | After CI passes        | All steps green, no warnings         |

---

## 11. Non-goals

- Deploy step (FIX-003)
- E2E/Playwright tests (need running server — deferred to FIX-007)
- Code coverage reporting
- Caching strategies beyond npm

---

## 12. Handoff prompt

```
Create `.github/workflows/ci.yml` for the Jawla project. The workflow runs on push to main and PRs to main. It uses ubuntu-latest with a Postgres 16 service container (user=postgres, password=postgres, db=jawla_test, port=5432, health check via pg_isready). PHP 8.3 with extensions bcmath/gd/intl/mbstring/pdo/pdo_pgsql/zip. Node 20 with npm cache. Steps: checkout, setup-php, setup-node, composer install, make lint, make typecheck, make test (with DB env vars), npm ci, make build, make test-offline. All Makefile commands already handle PAO_DISABLE=1 and memory limits.
```

---

## Output contract

```yaml
implementation_strategy:
  task: FIX-001 — GitHub Actions CI Workflow
  complexity: standard
  goal: CI pipeline passes green on every push/PR
  context: [No .github/ exists, tests need Postgres, Makefile has all commands]
  constraints: [ubuntu-latest, PHP 8.3, Node 20, pgsql driver]
  done_when: [.github/workflows/ci.yml exists, workflow runs green]
  verified_evidence:
    [
      Makefile commands work locally,
      Postgres service config matches phpunit.xml,
    ]
  assumptions:
    [Postgres 16 compatible with test suite, offline JS tests pass in CI]
  impact_map:
    confirmed: [.github/workflows/ci.yml is new file only]
    likely: [CI will pass on first run if Makefile commands work]
    unknown: [Whether any test has hidden timing dependency on local Postgres]
  chosen_approach:
    summary: Single workflow with Makefile commands and Postgres service container
    rationale: Reuses existing commands, consistent with local dev, minimal new code
    invalidated_if: Makefile commands fail in CI due to env differences
  alternatives_considered: [Inline commands, separate PHP/JS jobs]
  execution_steps: [Create dir, write workflow, verify locally, push and check]
  test_and_verification: [Makefile commands locally, CI green on push]
  approval_gates: []
  rollback_and_recovery: [Delete workflow file or revert commit]
  adaptive_checkpoints: [Pre-edit, post-file, post-push, post-green]
  parallel_work: []
  non_goals: [Deploy step, E2E tests, coverage, advanced caching]
  handoff_prompt: "Create .github/workflows/ci.yml for Jawla. Ubuntu-latest, Postgres 16 service (postgres/postgres/jawla_test), PHP 8.3 (bcmath/gd/intl/mbstring/pdo/pdo_pgsql/zip), Node 20. Steps: checkout, setup-php, setup-node, composer install, make lint, make typecheck, make test (with DB env vars), npm ci, make build, make test-offline."
  recommended_next_skill: ai-production-feature-builder
```
