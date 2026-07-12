# Contributing to Jawla

Even for a single-developer or AI-driven build, follow these rules — they
keep the git history clean and the CI green.

## Branching
- `main` is protected. All work goes through a pull request.
- Branch names: `feat/phase-N-short-slug`, `fix/short-slug`, `chore/…`, `docs/…`.

## Commits
- Conventional Commits: `feat:`, `fix:`, `chore:`, `docs:`, `test:`, `refactor:`.
- One logical change per commit. Reference the phase number when relevant.

## Before opening a PR
- `vendor/bin/pest` passes.
- `vendor/bin/pint` clean.
- `vendor/bin/phpstan analyse` clean at the configured level.
- `composer audit` and `npm audit --audit-level=high` clean.

## PR template
Fill every section in `.github/PULL_REQUEST_TEMPLATE.md`.
