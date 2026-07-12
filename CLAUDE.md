# Rules for Claude Code working in this repository

## Sources of truth
1. `Jawla_Production_Build_Guide.md` and `Jawla_Build_Guide_v1_Reference.md`
   are the primary spec. If they conflict with anything here, they win.
2. `docs/` files are the working reference for architecture, security, roles,
   design system, business rules, ZATCA, testing, deployment, and backups.

## Workflow
1. Build phase by phase per §12 of the production guide. Do not skip phases.
2. After every phase: run tests, print a summary, then commit with a clear
   message (`feat: phase N — <title>`).
3. Every phase must meet its Definition of Done before starting the next.

## Non-negotiable engineering rules (apply to every line of code)
- Secrets only in `.env`. Nothing secret ever reaches the frontend, JS
  bundles, Blade output, or logs. No API keys, tokens, or PATs in code.
- No shell execution: never use `exec`, `shell_exec`, `system`, `passthru`,
  `proc_open`, `eval`. No user input reaches a command line.
- All writes go through Form Requests or Livewire validation server-side.
  Use `$fillable` — never `$request->all()` into a model.
- All money mutations (invoices, payments, returns, expenses, cash box,
  van transfers) happen inside `DB::transaction()` via a Service. Never
  from a controller or Livewire component directly.
- Stock changes happen ONLY through `StockService`, which always writes a
  matching `stock_movements` row. Never update `stocks.quantity` directly.
- `Model::preventLazyLoading(! app()->isProduction())` in a base provider.
  Fix N+1 with `with()` — do not lower the guard.
- Pagination on every list. Never `->get()` an unbounded query.
- Password hashing = argon2id. TLS enforced. Sessions httpOnly + secure
  in prod + regenerated on login.
- Rate-limit login (5/min per IP+email) and every POST route (60/min per
  user). Custom bilingual 403/404/419/500 pages. `APP_DEBUG=false` in prod.
- Every destructive or financial action requires a confirmation modal that
  states the exact consequence, bilingually.
- RTL Arabic + LTR English work everywhere from the first commit.

## Non-negotiable business rules
- No negative van stock. Reject the sale at the service layer with a
  bilingual error message; never rely on UI alone.
- A sale creates: invoice + invoice_items + stock decrement + stock_movements
  + customer balance update, all inside one transaction. Any failure → full
  rollback, no partial state.
- Invoice/return numbers are sequential per company, generated server-side,
  never editable, never guessable.
- Reversal is a compensating transaction (never `delete()`) and is itself
  a logged activity linking to the original.

## Tests
- Write Pest tests alongside each phase (not in a "phase 13 test push").
- Feature tests must include the failure path for every money/stock flow.
- E2E: at minimum, rep day flow + admin master-data flow + RTL smoke.

## When in doubt
- Prefer the simplest solution that meets the guide.
- Do not introduce new packages beyond §2 of the main guide without asking.
- Do not modify `docs/BUSINESS_RULES.md` or `docs/SECURITY.md` — they are spec.
