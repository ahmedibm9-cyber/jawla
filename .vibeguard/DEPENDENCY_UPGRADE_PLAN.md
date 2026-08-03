# Dependency Upgrade Plan

## Objective

Modernize dependencies to latest stable versions while maintaining security and compatibility.

## Baseline

- **PHP**: 8.3.32
- **Node.js**: Current LTS
- **Composer audit**: No vulnerabilities
- **npm audit**: 0 vulnerabilities

## Current Dependency Inventory

### PHP Dependencies (Composer)

| Package           | Current | Latest | Type  | Breaking Changes         |
| ----------------- | ------- | ------ | ----- | ------------------------ |
| filament/filament | 4.12.5  | 5.7.5  | Major | Yes - major version jump |
| laravel/pint      | 1.30.2  | 1.30.3 | Patch | No                       |

### JavaScript Dependencies (npm)

| Package             | Current | Wanted  | Latest  | Type  |
| ------------------- | ------- | ------- | ------- | ----- |
| @playwright/test    | 1.61.1  | 1.62.1  | 1.62.1  | Minor |
| @sentry/browser     | 10.67.0 | 10.69.0 | 10.69.0 | Minor |
| @tailwindcss/vite   | 4.3.2   | 4.3.3   | 4.3.3   | Patch |
| concurrently        | 9.2.4   | 9.2.4   | 10.0.4  | Major |
| laravel-vite-plugin | 3.1.0   | 3.1.3   | 3.1.3   | Patch |
| lint-staged         | 17.1.0  | 17.3.0  | 17.3.0  | Minor |
| prettier            | 3.9.5   | 3.9.6   | 3.9.6   | Patch |
| tailwindcss         | 4.3.2   | 4.3.3   | 4.3.3   | Patch |
| vite                | 8.1.4   | 8.2.0   | 8.2.0   | Minor |

## Security Findings

- **Composer**: No security advisories
- **npm**: 0 vulnerabilities

## Upgrade Stages

### Stage 1: Patch Updates (Low Risk)

**Goal**: Apply non-breaking patch updates
**Dependencies**:

- laravel/pint: 1.30.2 → 1.30.3
- @tailwindcss/vite: 4.3.2 → 4.3.3
- laravel-vite-plugin: 3.1.0 → 3.1.3
- prettier: 3.9.5 → 3.9.6
- tailwindcss: 4.3.2 → 4.3.3

**Verification**:

- Run `composer update laravel/pint`
- Run `npm update @tailwindcss/vite laravel-vite-plugin prettier tailwindcss`
- Run `npm run build`
- Run tests

### Stage 2: Minor Updates (Medium Risk)

**Goal**: Apply minor version updates
**Dependencies**:

- @playwright/test: 1.61.1 → 1.62.1
- @sentry/browser: 10.67.0 → 10.69.0
- lint-staged: 17.1.0 → 17.3.0
- vite: 8.1.4 → 8.2.0

**Verification**:

- Run `npm update @playwright/test @sentry/browser lint-staged vite`
- Run `npm run build`
- Run tests including browser tests

### Stage 3: Major Updates (High Risk)

**Goal**: Evaluate major version upgrades
**Dependencies**:

- filament/filament: 4.12.5 → 5.7.5 (Laravel admin panel - major breaking changes expected)
- concurrently: 9.2.4 → 10.0.4 (CLI tool - likely safe)

**Note**: filament/filament 5.x is a major upgrade requiring careful migration. This should be deferred to a dedicated upgrade cycle.

## Rollback Points

1. **Before Stage 1**: `git stash` or create branch
2. **Before Stage 2**: Tag current state
3. **Before Stage 3**: Not recommended until Stage 1-2 verified

## Deferred Items

- **filament/filament 5.x**: Major version upgrade requiring dedicated migration plan
- **concurrently 10.x**: Evaluate breaking changes before upgrading

## Status

- **Current**: Stage 2 completed
- **Completed**:
  - Stage 1:
    - laravel/pint: 1.30.2 → 1.30.3 ✓
    - @tailwindcss/vite: 4.3.2 → 4.3.3 ✓
    - laravel-vite-plugin: 3.1.0 → 3.1.3 ✓
    - prettier: 3.9.5 → 3.9.6 ✓
    - tailwindcss: 4.3.2 → 4.3.3 ✓
  - Stage 2:
    - @playwright/test: 1.61.1 → 1.62.1 ✓
    - @sentry/browser: 10.67.0 → 10.69.0 ✓
    - lint-staged: 17.1.0 → 17.3.0 ✓
    - vite: 8.1.4 → 8.2.0 ✓
- **Verification**: Build successful, application working
- **Next**: Stage 3 evaluation (major updates)
