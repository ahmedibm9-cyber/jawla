# Troubleshooting: Common issues

## "Relation does not exist" in tests

Run `php artisan migrate --force` against the test database. Unit tests
may run before any RefreshDatabase test migrates the schema.

## Vite build fails

Run `npm ci` to reinstall from lockfile. Check Node version matches
`.nvmrc` or `package.json` engines.

## Playwright tests timeout

Increase timeout in `tests/Pest.php` `beforeEach`. Check that the app
server is running and accessible at the configured URL.

## Stock quantity mismatch

Stock must only be changed via `StockService`. If `stocks.quantity` and
`stock_movements` don't reconcile, check for direct `DB::table('stocks')->update()`
calls — those bypass the movement logging.

## Invoice number gap

Numbers are per-company and sequential. A gap means a transaction was
rolled back. Check the invoice table for the missing sequence and verify
the `InvoiceService` transaction boundaries.
