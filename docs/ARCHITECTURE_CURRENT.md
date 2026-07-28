# Jawla — Current Architecture

**Last updated:** 2026-07-28
**Supersedes:** All prior architecture diagrams in `docs/`

## System Overview

```
┌─────────────────────────────────────────────────────┐
│                    CLIENTS                          │
│  ┌──────────┐  ┌──────────┐  ┌──────────────────┐  │
│  │ Rep PWA  │  │  Admin   │  │  External APIs   │  │
│  │ (Phone)  │  │ (Desktop)│  │  (Maps, Sentry)  │  │
│  └────┬─────┘  └────┬─────┘  └──────────────────┘  │
│       │              │                               │
└───────┼──────────────┼───────────────────────────────┘
        │              │
   ┌────▼────┐    ┌────▼────┐
   │Service  │    │Filament │
   │Worker   │    │  Admin  │
   │(PWA)    │    │  Panel  │
   └────┬────┘    └────┬────┘
        │              │
        └──────┬───────┘
               │
┌──────────────▼───────────────────────────────────────┐
│              APPLICATION (Laravel 13)                │
│                                                     │
│  ┌─────────────────────────────────────────────┐    │
│  │              HTTP Layer                      │    │
│  │  Routes: web.php, Filament, Livewire        │    │
│  │  Middleware: auth, company context, RBAC     │    │
│  └──────────────────┬──────────────────────────┘    │
│                     │                               │
│  ┌──────────────────▼──────────────────────────┐    │
│  │           Service Layer (Business Logic)     │    │
│  │  InvoiceService, PaymentService,             │    │
│  │  StockService, VanTransferService,           │    │
│  │  ReturnService, ExpenseService,              │    │
│  │  ComplaintService, VisitReportService,       │    │
│  │  PdfService, SyncService                     │    │
│  └──────────────────┬──────────────────────────┘    │
│                     │                               │
│  ┌──────────────────▼──────────────────────────┐    │
│  │            Data Layer (Eloquent)             │    │
│  │  Models: Company, User, Invoice, Stock,      │    │
│  │  Customer, VanTransfer, Payment, Return,     │    │
│  │  SyncReceipt, etc.                           │    │
│  │  Traits: BelongsToCompany, AppendOnly        │    │
│  └──────────────────┬──────────────────────────┘    │
│                     │                               │
└─────────────────────┼───────────────────────────────┘
                      │
┌─────────────────────▼───────────────────────────────┐
│              INFRASTRUCTURE                         │
│                                                     │
│  ┌──────────────┐  ┌──────────────┐  ┌───────────┐ │
│  │  PostgreSQL   │  │   Railway    │  │   Sentry  │ │
│  │  Database     │  │   Bucket     │  │   Errors  │ │
│  │  (Primary)    │  │   (Photos)   │  │   + Perf  │ │
│  └──────────────┘  └──────────────┘  └───────────┘ │
│                                                     │
│  Railway Platform: Docker, php-fpm + nginx, 2 repl. │
└─────────────────────────────────────────────────────┘
```

## Key Flows

### Rep Daily Flow (Offline-Capable)

1. Rep opens `/app` → logs in → work session starts
2. Visits customers along route (GPS tracked)
3. Creates invoices, payments, returns, expenses offline (queued in IndexedDB outbox)
4. On reconnect: sync engine flushes outbox to `POST /app/sync` with idempotency
5. End of day: cash reconciliation, submit visit reports

### Sync Protocol

- **Client:** IndexedDB outbox → sorted by dependency → batched POST to `/app/sync`
- **Server:** SyncService processes each operation in a transaction, stores receipt
- **Idempotency:** `(company_id, idempotency_key)` unique constraint + lockForUpdate
- **Versioning:** `X-Sync-Protocol-Version` header (currently v1)
- **Payload hashing:** SHA-256 for mismatch detection
- **Dependency ordering:** `dependsOn`/`tempId` for operation ordering

### Invoice Lifecycle

1. Draft → Issued (number allocated, snapshot frozen)
2. Issued → Paid (payment allocated)
3. Paid → Credit Note (compensating reversal)
4. Reversal → Refund (cash returned)

### Stock Lifecycle

1. Warehouse import → Stock count
2. Van transfer (request → approve → in-transit → received)
3. Sale deduction (FEFO batch selection)
4. Return → Restock or quarantine

## Roles & Access

| Role      | Panel               | Capabilities                                   |
| --------- | ------------------- | ---------------------------------------------- |
| Admin     | `/admin` (Filament) | Full access, company settings, user management |
| Manager   | `/admin` (Filament) | Team oversight, approvals, reports             |
| Rep       | `/app` (Livewire)   | Visit, sell, collect, return, expense, offline |
| Warehouse | `/admin` (Filament) | Stock management, transfers                    |
| Finance   | `/admin` (Filament) | Reports, reconciliation, payments              |

## Security

- Multi-tenancy: `company_id` on all models, enforced via global scopes + service context
- RBAC: Spatie permission with 5 canonical roles, no super-admin bypass
- Sessions: Database driver, httpOnly + secure, regenerated on login
- Passwords: Argon2id
- Financial: All mutations in `DB::transaction`, append-only ledgers
- File uploads: EXIF stripped, private storage, signed URLs
- Sentry scrubber masks credentials/tokens before events leave the app

## Deployment

- Platform: Railway (Docker, php-fpm + nginx)
- Database: PostgreSQL 16 (Railway managed)
- Storage: Railway bucket (S3-compatible) for photos
- CI: GitHub Actions (lint + test with PostgreSQL service)
- Health: `GET /health` → JSON with DB + cache status
- Replicas: 2 for availability
