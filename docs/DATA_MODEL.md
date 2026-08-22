# DATA_MODEL — Jawla Enhancement Suite

## New Columns on Existing Tables

### `customers` table

```sql
ALTER TABLE customers ADD COLUMN checkin_radius_m INTEGER DEFAULT 100;
-- Range: 50-500, configurable per customer
```

### `photos` table (or metadata)

```sql
ALTER TABLE photos ADD COLUMN latitude DECIMAL(10, 7);
ALTER TABLE photos ADD COLUMN longitude DECIMAL(10, 7);
ALTER TABLE photos ADD COLUMN gps_accuracy_m DECIMAL(5, 2);
ALTER TABLE photos ADD COLUMN gps_verified BOOLEAN DEFAULT false;
```

### `users` table

```sql
-- No schema changes, but new preferences:
-- dashboard_preset: 'executive' | 'operations' | 'sales' | 'finance'
-- (stored via existing setPreference() mechanism)
```

---

## New Tables

### `webauthn_credentials`

```sql
CREATE TABLE webauthn_credentials (
  id BIGSERIAL PRIMARY KEY,
  user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  credential_id VARCHAR(255) NOT NULL UNIQUE,
  public_key TEXT NOT NULL,
  counter BIGINT DEFAULT 0,
  created_at TIMESTAMP DEFAULT NOW(),
  last_used_at TIMESTAMP
);

CREATE INDEX idx_webauthn_credentials_user ON webauthn_credentials(user_id);
CREATE INDEX idx_webauthn_credentials_credential ON webauthn_credentials(credential_id);
```

### `user_devices`

```sql
CREATE TABLE user_devices (
  id BIGSERIAL PRIMARY KEY,
  user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  fingerprint VARCHAR(64) NOT NULL,
  status VARCHAR(20) DEFAULT 'flagged', -- known, flagged, blocked
  ip_address INET,
  user_agent TEXT,
  last_seen_at TIMESTAMP DEFAULT NOW(),
  created_at TIMESTAMP DEFAULT NOW(),
  UNIQUE(user_id, fingerprint)
);

CREATE INDEX idx_user_devices_user ON user_devices(user_id);
CREATE INDEX idx_user_devices_status ON user_devices(status);
```

### `route_suggestions` (optional, for learning)

```sql
CREATE TABLE route_suggestions (
  id BIGSERIAL PRIMARY KEY,
  company_id BIGINT NOT NULL,
  user_id BIGINT NOT NULL,
  work_session_id BIGINT NOT NULL,
  suggested_order JSONB NOT NULL,
  accepted BOOLEAN DEFAULT false,
  actual_order JSONB,
  created_at TIMESTAMP DEFAULT NOW()
);
```

---

## Indexes to Add

```sql
-- Performance indexes for time-series queries
CREATE INDEX idx_invoices_created_at ON invoices(created_at);
CREATE INDEX idx_invoices_company_created ON invoices(company_id, created_at);
CREATE INDEX idx_sales_orders_created_at ON sales_orders(created_at);
CREATE INDEX idx_sales_orders_company_created ON sales_orders(company_id, created_at);
CREATE INDEX idx_payments_created_at ON payments(created_at);
CREATE INDEX idx_payments_company_created ON payments(company_id, created_at);
CREATE INDEX idx_visits_created_at ON visits(created_at);
CREATE INDEX idx_visits_company_created ON visits(company_id, created_at);
CREATE INDEX idx_location_pings_recorded_at ON location_pings(recorded_at);
CREATE INDEX idx_location_pings_company_recorded ON location_pings(company_id, recorded_at);

-- PostGIS spatial indexes (Phase 2)
CREATE EXTENSION IF NOT EXISTS postgis;

ALTER TABLE customers ADD COLUMN position GEOGRAPHY(POINT, 4326);
UPDATE customers SET position = ST_SetSRID(ST_MakePoint(longitude, latitude), 4326)
WHERE latitude IS NOT NULL AND longitude IS NOT NULL;
CREATE INDEX idx_customers_position ON customers USING GIST(position);

ALTER TABLE location_pings ADD COLUMN position GEOGRAPHY(POINT, 4326);
UPDATE location_pings SET position = ST_SetSRID(ST_MakePoint(longitude, latitude), 4326);
CREATE INDEX idx_location_pings_position ON location_pings USING GIST(position);
CREATE INDEX idx_location_pings_company_position ON location_pings(company_id, position);
```

---

## Migration Strategy

1. **Phase 1:** Add new columns to `customers`, `photos`. Add time-series indexes.
2. **Phase 2:** Enable PostGIS extension. Add `position` geography columns. Populate from lat/lng. Add spatial indexes.
3. **Phase 3:** No schema changes (uses existing sync engine + new client-side logic).
4. **Phase 4:** Create `webauthn_credentials` and `user_devices` tables.

**Rollback:** Each phase has independent migrations. Rollback drops new tables/columns.

**Data Safety:** No destructive changes. New columns are nullable or have defaults. PostGIS columns are derived from existing lat/lng.
