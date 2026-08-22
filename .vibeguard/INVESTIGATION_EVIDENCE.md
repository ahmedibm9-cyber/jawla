investigation_evidence:
case: Hardcoded demo user password in migration d2e6dff
mode: defect_hunt
repository:
root: C:\projects\jawla
branch: master
commit: d2e6dff (Add migration to update demo user credentials: rename emails to role names, set password to 123456789)
commands_executed: - git show d2e6dff -- database/migrations/2026_08_17_194349_update_demo_user_credentials.php - git log --oneline --all -- database/migrations/2026_08_17_194349_update_demo_user_credentials.php
paths_preserved: - database/migrations/2026_08_17_194349_update_demo_user_credentials.php - .env
sanitized_logs: - Migration d2e6dff sets password Hash::make('123456789') for 12 user emails mapped from @jawla.test suffixes to role names
provenance: - Migration author: ahmedibm9-cyber (GitHub) - Migration date: Mon Aug 17 19:48:30 2026 +0300
verification_attempts: - Confirmed migration d2e6dff has empty down() — one-way data migration with no rollback - Checked .env: APP_ENV=production, JAWLA_MODE=production
evidence_collisions: []
notes: - Migration d2e6dff targets jawla.test domain emails only (not production per .env) - Hardcoded password '123456789' is the primary security concern - Demo seeder findings excluded per user instruction
next_steps: - Remove hardcoded password from migration or gate it behind environment check - Add test to verify no hardcoded passwords exist in database migrations
