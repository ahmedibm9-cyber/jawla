# Open Questions

## Blocking Questions

| ID    | Question                                            | Why it matters                                                        | Evidence checked         | Safest resolution                           | Blocks                   |
| ----- | --------------------------------------------------- | --------------------------------------------------------------------- | ------------------------ | ------------------------------------------- | ------------------------ |
| Q-001 | What is the current production deployment status?   | Determines if changes can affect live users                           | railway.toml, Dockerfile | Check Railway dashboard or ask team         | Production changes       |
| Q-002 | How many active users does the system have?         | Determines load testing requirements and priority of performance work | None                     | Ask client/team                             | Performance optimization |
| Q-003 | What is the offline sync reliability in production? | Critical for field rep operations; offline-first is core value prop   | Services/Sync/, tests    | Review sync_receipts table in production DB | Offline feature changes  |

## Non-Blocking Questions

| ID    | Question                                                | Why it matters                                     | Current hypothesis                                                | Confidence | Resolution path                             |
| ----- | ------------------------------------------------------- | -------------------------------------------------- | ----------------------------------------------------------------- | :--------: | ------------------------------------------- |
| Q-101 | Are there any unused models in the 90+ model directory? | Maintenance overhead, confusion for new developers | Likely some are referenced only in tests or not at all            |     50     | Grep for model usage across codebase        |
| Q-102 | What is the current ZATCA integration status?           | Saudi e-invoicing compliance                       | Basic QR TLV encoding exists; full API integration may be partial |     60     | Check Services/Eta/ implementation status   |
| Q-103 | How does the approval workflow handle timeouts?         | Long-running approvals could block operations      | Timeout and escalation mechanisms mentioned in ARCHITECTURE.md    |     70     | Review ApprovalService implementation       |
| Q-104 | What happens when Redis is unavailable?                 | Cache/session failure could degrade UX             | Laravel has file cache fallback                                   |     65     | Check config/cache.php driver configuration |
| Q-105 | Are there any security audit results from OWASP ZAP?    | Determines security posture                        | Weekly scans mentioned in SECURITY.md                             |     55     | Check GitHub Actions or staging logs        |

## Contradictions Requiring Owner Input

| ID    | Topic            | Conflicting evidence                               | Recommended owner or source                        |
| ----- | ---------------- | -------------------------------------------------- | -------------------------------------------------- |
| C-001 | Filament version | README says "Filament 4", composer.json has "^4.0" | Development team — verify actual installed version |
| C-002 | Test count       | README says "975 tests", actual count may differ   | Run `php artisan test` to verify                   |

## Missing Access or Environment

| Need                       | Impact                                                 | Workaround used                                | Required follow-up       |
| -------------------------- | ------------------------------------------------------ | ---------------------------------------------- | ------------------------ |
| Production database access | Cannot verify actual data patterns, sync reliability   | Read-only queries via Railway CLI if available | Request read-only access |
| Railway dashboard access   | Cannot verify deployment status, environment variables | Check via CLI if authenticated                 | Request access           |
| Production logs            | Cannot verify error patterns, performance              | Sentry dashboard access                        | Request Sentry access    |
