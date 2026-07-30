# Changelog

All notable changes to Jawla will be documented in this file.

Format based on [Keep a Changelog](https://keepachangelog.com/).

## [Unreleased]

### Added

- AGENTS.md with comprehensive agent instructions
- Makefile for stable command interface
- scripts/verify for single-command validation
- Feature spec template in specs/templates/
- Runbooks for deploy failure, database restore, high error rate, credential rotation
- Troubleshooting guide for common issues
- Threat model and secrets policy in docs/SECURITY.md
- Deploy workflow with staging/production environments
- Security issue template
- database/README.md with migration rules
- Dashboard widget drag-drop reorder with persisted user preference
- Company scoping for Visit model with migration

### Fixed

- Dark mode: replaced `prefers-color-scheme` media query with `.dark` class selector
- Permission checks added to Customer, Product, GoodsInTransit, and Task policies
- RolePolicy: removed deprecated `HandlesAuthorization` trait, fixed type hints
- 56 inline translation ternaries replaced with `l()` helper across 11 Blade views
- `wire:key` added to 7 `@foreach` loops to prevent DOM diffing issues
- Badge styling in van-transfers now uses design-system classes
- `log-return` alert replaced with `<x-ds.toast>` for consistency
- Deleted dead `autocomplete` Blade component
- CI pipeline: added `.env` setup before PHPStan analysis

## [0.1.0] - 2026-07-27

### Added

- Initial release with AM1→AM9 demo flow
- Admin panel (Filament 4) and Rep PWA (Livewire 3)
- Stock management with atomic operations
- Invoice flow with VAT and ZATCA QR encoding
- Role-based access control (5 roles)
- Bilingual Arabic/English with RTL support
- CI pipeline with Pest tests, Pint linting, PHPStan
- Security scanning with Gitleaks and OWASP ZAP
- Railway deployment with PostgreSQL and Redis
- Encrypted off-host backup with restore drill
