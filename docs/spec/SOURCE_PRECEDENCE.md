# Source Precedence

If sources conflict, the PRD owns what must exist, the amendment owns
sequencing, and the repository's source-of-truth rules (AGENTS.md) decide all
remaining conflicts. Never let an AI model silently choose a business rule.

## Binding order

1. `docs/spec/Jawla_Beta_PRD_v1.1.md` — what must exist in the beta
2. `docs/spec/Jawla_Build_Guide_v1.1_Amendment.md` — sequencing and technical amendments
3. `AGENTS.md` — repository rules (security, money, stock, testing)
4. `docs/BUSINESS_RULES.md` — non-negotiable business rules (do not modify)
5. `docs/SECURITY.md` — security policy (do not modify)
6. `docs/ROLES_MATRIX.md` — roles and permissions
7. `docs/DESIGN_SYSTEM.md` — UI standards
8. `docs/TESTING.md` — testing strategy
9. `docs/DEPLOYMENT.md` — deployment procedures
10. `docs/BACKUP_RESTORE.md` — backup and restore procedures

## Historical documents (not authoritative)

- `docs/CHANGES_REPORT.md` — historical change log, not a spec
- `Jawla_BETA_Build_Guide.md` — superseded by v1.1 amendment
- `Jawla_Build_Guide_v1_Reference.md` — reference only, narrowed by amendment
- `Jawla_Production_Build_Guide.md` — full production guide, not beta scope
