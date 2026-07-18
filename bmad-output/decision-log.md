# Decision Log

## 2026-07-18 — UI Control Module Audit

### Decisions Made

| # | Decision | Rationale | Status |
|---|----------|-----------|--------|
| D1 | Prioritize confirmation modals for all financial actions | Zero confirmation modals across 8 mutating pages is a critical safety gap | Accepted |
| D2 | Add tab bar to Collect Payment, Log Return, Log Expense | Navigation dead-ends break mobile UX on iOS PWA | Accepted |
| D3 | Register service worker in layout | PWA is not installable/offline-capable despite having manifest + sw.js | Accepted |
| D4 | Fix `$recalcCart()` no-op in SalesFlow | Cart totals are wrong; no tax calculation occurs | Accepted |
| D5 | Replace native `<select>` with searchable autocomplete | 50+ item selects are unusable on mobile touch screens | Accepted |
| D6 | Use existing `<x-ds-modal>`, `<x-ds-skeleton>`, `<x-ds-empty>` components | Design system components exist but are unused; raw HTML used instead | Accepted |
| D7 | Add photo capture to Visit Flow, Complaints, Returns | Proof-of-presence and evidence capture is critical for field operations | Accepted |
| D8 | Wire up existing service cancel methods for undo | `PaymentService::cancel()`, `ExpenseService::cancel()`, `ReturnService::cancel()` exist but are never exposed | Accepted |

### Risks Identified

| Risk | Impact | Mitigation |
|------|--------|------------|
| Financial actions have no confirmation | Accidental payments/invoices | Add confirmation modals (D1) |
| No skeleton loading on any page | Poor perceived performance on slow connections | Add skeleton states (P1) |
| Service worker not registered | PWA not installable, not offline-capable | Register in layout (D3) |
| Cart recalculation is no-op | Wrong totals, no tax | Implement recalculation (D4) |

### Pending Decisions

| Question | Options | Recommendation |
|----------|---------|----------------|
| Should we add barcode scanning? | Yes / No / Later | Later (P3) — requires camera API + barcode library |
| Should we add voice notes? | Yes / No / Later | Later (P3) — Web Speech API has limited browser support |
| Should we add dark mode? | Yes / No / Later | Later (P3) — not requested by client |
| Should we virtualize lists? | Yes / No / Later | Later — current lists are < 100 items |
