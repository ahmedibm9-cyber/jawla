# Backup and Restore

## Automatic backups

Railway provides daily automatic backups for PostgreSQL services.

**Where to find them:**
1. Railway dashboard → Postgres service → Backups tab
2. Click "Restore" to restore to a new database
3. Click "Download" to download a `.dump` file

**Retention:** Railway retains backups for 7 days on the Pro plan.

---

## Manual backup

### From Railway (recommended)

```bash
railway run pg_dump $DATABASE_URL > backup_$(date +%Y%m%d_%H%M%S).sql
```

### From local (if DATABASE_URL is set in .env)

```bash
pg_dump $DATABASE_URL > backup_$(date +%Y%m%d_%H%M%S).sql
```

### What's included

All database tables:

| Table | Description |
|-------|-------------|
| `companies` | Company profiles (multi-tenant) |
| `users` | User accounts, roles, hashed passwords |
| `warehouses` | Warehouse locations |
| `product_categories` | Product classification |
| `products` | Product catalog (AR/EN names, prices) |
| `stocks` | Per-product, per-warehouse quantities |
| `stock_movements` | Every stock change with reason |
| `routes` | Sales rep daily routes |
| `route_user` | Route-rep assignments |
| `customers` | Customer records with GPS coordinates |
| `work_sessions` | Check-in / check-out sessions |
| `visits` | Visit logs with GPS + timestamps |
| `invoices` | Invoice headers |
| `invoice_items` | Invoice line items |
| `payments` | Payment records |
| `returns` | Return headers |
| `return_items` | Return line items |
| `expenses` | Expense records |
| `van_transfers` | Van stock transfer headers |
| `van_transfer_items` | Van stock transfer line items |
| `cash_boxes` | Cash box records |
| `cache` | Laravel cache table |
| `sessions` | Laravel session table |
| `permissions`, `roles`, ... | Spatie permission tables |

### What's NOT included

- **File uploads** — photos stored in `storage/app/public` (or S3 if configured)
- **.env secrets** — must be re-configured manually after restore
- **Queue jobs** — any pending jobs in the `jobs` table are lost on restore

---

## Restore

### To Railway database

```bash
railway run psql $DATABASE_URL < backup_20260803_120000.sql
```

### To a fresh database

```bash
psql $DATABASE_URL < backup_20260803_120000.sql
```

### After restore

1. Run `php artisan config:cache` to rebuild cached config
2. Run `php artisan route:cache` to rebuild cached routes
3. Verify: `php artisan migrate:status` — all migrations should show "Yes"

---

## Recovery objectives

| Metric | Value | Notes |
|--------|-------|-------|
| **RPO** (Recovery Point Objective) | 24 hours | Daily automatic backups |
| **RTO** (Recovery Time Objective) | < 1 hour | Full restore from backup |

---

## Verification checklist

After any backup/restore cycle:

- [ ] `SELECT COUNT(*) FROM users` — matches pre-backup count
- [ ] `SELECT COUNT(*) FROM invoices` — matches pre-backup count
- [ ] `SELECT COUNT(*) FROM customers` — matches pre-backup count
- [ ] Login works with existing credentials
- [ ] Offline sync queue processes without errors
