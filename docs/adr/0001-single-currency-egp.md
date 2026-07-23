# ADR 0001: Single Base Currency (EGP) for v1

**Status:** Accepted  
**Date:** 2026-07-12

## Context

Jawla serves **Global Plastic Company (GPC)** — an Egyptian plastics/chemicals trading firm. GPC operates primarily in Egypt (EGP). A Saudi Arabia entity is planned for v2 but not in scope for the beta/MVP.

The client explicitly chose **EGP as the sole base currency**.

## Decision

All sales, invoicing, collections, returns, and financial reporting in v1 use **EGP** as the single currency.

Purchase orders and supplier quotations may still use foreign currencies (USD, CNY, EUR) for international supplier transactions, with the exchange rate stored on each document for EGP conversion.

## Rationale

- **Simplified reporting** — single currency, single set of numbers
- **Client preference** — Amr explicitly chose this
- **No inter-company in v1** — Saudi entity deferred, so no cross-currency concern

## Consequences

### Positive

- Single currency throughout sales flow (prices, invoices, payments, reports)
- No exchange rate tables or rate lookup needed for sales transactions
- Simpler audit trail — one currency, one set of numbers

### Negative

- If Saudi entity is added in v2 with SAR, currency model will need revisiting — either keep EGP globally (current plan) or add per-entity currencies

### Mitigation

- Store the exchange rate on each PO for international purchases
- Saudi entity decision can be revisited independently in v2
