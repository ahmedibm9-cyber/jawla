# Implementation Packet: Task 1.1 — Create Migration Files

**Date:** 2026-08-24  
**Complexity:** Standard  
**Status:** Ready for Execution

---

## Task

Create migration files for M7 tables (todos, tickets, ticket_status_history, requests, calls) plus verify/fix the visits.is_out_of_route column.

---

## Goal

Create 4 new migration files and verify 1 existing column, enabling all M7 features (calendar, todos, tickets, requests, calls, performance dashboard).

---

## Context

### Repository Patterns Discovered

| Pattern          | Evidence                                                               | File                      |
| ---------------- | ---------------------------------------------------------------------- | ------------------------- |
| Primary keys     | `bigIncrements('id')` or `$table->id()`                                | All migrations            |
| Tenant isolation | `foreignId('company_id')->constrained('companies')->cascadeOnDelete()` | All migrations            |
| User references  | `foreignId('user_id')->constrained('users')->cascadeOnDelete()`        | All migrations            |
| Status fields    | `enum('status', [...])`                                                | complaints, tasks, visits |
| Timestamps       | `timestamps()` or `timestampsTz()`                                     | All migrations            |
| Foreign keys     | `foreignId()->constrained()->cascadeOnDelete()`                        | All migrations            |
| Indexes          | `$table->index([...])`                                                 | All migrations            |
| Migration syntax | `return new class extends Migration`                                   | All migrations            |
| Company scoping  | `BelongsToCompany` trait (auto-fills company_id)                       | All models                |

### Critical Finding: Existing Tables

| M7 Table                 | Existing Table                          | Overlap                               | Decision                                                                                      |
| ------------------------ | --------------------------------------- | ------------------------------------- | --------------------------------------------------------------------------------------------- |
| `todos`                  | `tasks` (expanded in 2026_08_03_120000) | Both track tasks with status/priority | **Create separate `todos`** — simpler, rep-focused, different workflow                        |
| `requests`               | `approval_requests` (2026_08_03_120000) | Both handle approval workflows        | **Create separate `requests`** — simpler, manager-focused, different from multi-step approval |
| `ticket_status_history`  | N/A                                     | None                                  | **Create** — audit trail for tickets                                                          |
| `tickets`                | N/A                                     | None                                  | **Create** — support tickets                                                                  |
| `calls`                  | N/A                                     | None                                  | **Create** — phone call logs                                                                  |
| `visits.is_out_of_route` | Already exists                          | Column exists                         | **Skip migration** — already in visits table                                                  |

### Existing Table Structures Referenced

- `visits`: Has `is_out_of_route` boolean column (line 19 of 2026_01_01_000012)
- `customer_contacts`: Has `id`, `company_id`, `customer_id`, `name`, `phone` (line 26-38 of 2026_08_03_140000)
- `tasks`: Has `id`, `company_id`, `status`, `priority`, `due_date` (expanded in 2026_08_03_120000)
- `approval_requests`: Has `id`, `company_id`, `status`, `submitted_by` (line 38-49 of 2026_08_03_120000)

---

## Constraints

1. **PostgreSQL**: Database is PostgreSQL (not SQLite)
2. **Multi-tenant**: All tables must have `company_id` with cascade delete
3. **UUID primary keys**: M7_DATA_MODEL.md specifies UUID PKs, but existing codebase uses `bigIncrements('id')` — **use bigIncrements** for consistency
4. **No breaking changes**: Migrations must not break existing data
5. **Immutable after release**: Migrations cannot be modified after deployment
6. **No new dependencies**: Use existing Laravel schema builder only

---

## Done When

- [ ] 4 migration files created and runnable
- [ ] `php artisan migrate` passes without errors
- [ ] All foreign keys enforced
- [ ] All indexes created
- [ ] Rollback (`php artisan migrate:rollback`) works
- [ ] No existing tests broken

---

## Verified Evidence

| Item                            | Status       | Source                                                                |
| ------------------------------- | ------------ | --------------------------------------------------------------------- |
| Visits.is_out_of_route exists   | ✅ Confirmed | 2026_01_01_000012_create_visits_table.php:19                          |
| tasks table has status/priority | ✅ Confirmed | 2026_08_03_120000_expand_tasks_and_create_approval_workflow.php       |
| approval_requests exists        | ✅ Confirmed | 2026_08_03_120000_expand_tasks_and_create_approval_workflow.php:38-49 |
| customer_contacts exists        | ✅ Confirmed | 2026_08_03_140000_create_customer_structure_tables.php:26-38          |
| BelongsToCompany trait          | ✅ Confirmed | app/Models/Concerns/BelongsToCompany.php                              |
| PostgreSQL driver               | ✅ Confirmed | AGENTS.md line 43                                                     |

---

## Assumptions

1. **todos vs tasks**: Creating separate `todos` table is correct because:
   - Tasks have approval workflow (reviewer_id, final_approver_id, requires_approval)
   - Todos are simpler rep-focused items (title, priority, due_date, status)
   - Different UI/UX (todos: checkboxes, tasks: full workflow)

2. **requests vs approval_requests**: Creating separate `requests` table is correct because:
   - approval_requests use polymorphic morphs (approvable_type/id)
   - approval_requests have multi-step approval (approval_steps)
   - requests are simpler manager approval (approve/reject with reason)

3. **Primary key format**: Using `bigIncrements('id')` instead of UUID to match existing codebase

---

## Impact Map

### Confirmed

| Area                 | Impact                  | Evidence                                       |
| -------------------- | ----------------------- | ---------------------------------------------- |
| database/migrations/ | 4 new files             | Task scope                                     |
| app/Models/          | 5 new models (Task 1.2) | Downstream task                                |
| tests/               | Migration tests needed  | AGENTS.md: "Tests must use isolated databases" |

### Likely

| Area             | Impact                         | Evidence    |
| ---------------- | ------------------------------ | ----------- |
| app/Services/    | New service classes (Task 1.3) | M7_TASKS.md |
| resources/views/ | New Blade templates (Phase 3)  | M7_TASKS.md |

### Unknown

| Area           | Impact           | Evidence   |
| -------------- | ---------------- | ---------- |
| Existing tests | May need updates | Unverified |

---

## Chosen Approach

### Summary

Create 4 new migration files following existing codebase patterns:

1. `create_todos_table.php` — Simple task tracking for reps
2. `create_tickets_table.php` — Support ticket workflow
3. `create_ticket_status_history_table.php` — Audit trail for tickets
4. `create_calls_table.php` — Phone call logging

Skip `visits.is_out_of_route` migration (already exists).

### Rationale

- Follows existing patterns (bigIncrements, foreignId, enum, timestamps)
- Minimal change surface (4 files, no modifications to existing tables)
- No new dependencies
- Reversible (each migration has down() method)
- Consistent with codebase conventions

### Invalidated If

- Existing tests break after migration
- PostgreSQL-specific features needed (JSONB, arrays, etc.)
- Performance issues with indexes on large tables

---

## Alternatives Considered

| Alternative               | Pros                     | Cons                                           | Decision     |
| ------------------------- | ------------------------ | ---------------------------------------------- | ------------ |
| Reuse `tasks` table       | No new table             | Complex approval workflow not needed for todos | **Rejected** |
| Reuse `approval_requests` | No new table             | Polymorphic morphs add complexity              | **Rejected** |
| Use UUID primary keys     | Matches M7_DATA_MODEL.md | Inconsistent with existing codebase            | **Rejected** |
| Single migration file     | Fewer files              | Harder to rollback individual tables           | **Rejected** |

---

## Execution Steps

### Step 1: Create todos migration

**File**: `database/migrations/2026_08_24_000001_create_todos_table.php`

**Schema**:

```php
Schema::create('todos', function (Blueprint $table): void {
    $table->bigIncrements('id');
    $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
    $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
    $table->string('title');
    $table->text('description')->nullable();
    $table->enum('priority', ['low', 'medium', 'high'])->default('medium');
    $table->enum('status', ['new', 'done'])->default('new');
    $table->date('due_date');
    $table->timestamp('completed_at')->nullable();
    $table->boolean('is_active')->default(true);
    $table->timestamps();
    $table->index(['company_id', 'user_id']);
    $table->index(['status']);
    $table->index(['due_date']);
    $table->index(['company_id', 'is_active']);
});
```

**Verification**: `php artisan migrate`

---

### Step 2: Create tickets migration

**File**: `database/migrations/2026_08_24_000002_create_tickets_table.php`

**Schema**:

```php
Schema::create('tickets', function (Blueprint $table): void {
    $table->bigIncrements('id');
    $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
    $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
    $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
    $table->string('title');
    $table->text('description');
    $table->enum('status', ['new', 'in_progress', 'completed', 'cancelled', 'disabled'])->default('new');
    $table->enum('priority', ['low', 'medium', 'high'])->default('medium');
    $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamp('resolved_at')->nullable();
    $table->boolean('is_active')->default(true);
    $table->timestamps();
    $table->index(['company_id', 'status']);
    $table->index(['company_id', 'user_id']);
    $table->index(['assigned_to']);
    $table->index(['customer_id']);
});
```

**Verification**: `php artisan migrate`

---

### Step 3: Create ticket_status_history migration

**File**: `database/migrations/2026_08_24_000003_create_ticket_status_history_table.php`

**Schema**:

```php
Schema::create('ticket_status_history', function (Blueprint $table): void {
    $table->bigIncrements('id');
    $table->foreignId('ticket_id')->constrained('tickets')->cascadeOnDelete();
    $table->string('old_status', 50)->nullable();
    $table->string('new_status', 50);
    $table->foreignId('changed_by')->constrained('users')->cascadeOnDelete();
    $table->timestamp('changed_at');
    $table->text('notes')->nullable();
    $table->index(['ticket_id']);
});
```

**Verification**: `php artisan migrate`

---

### Step 4: Create calls migration

**File**: `database/migrations/2026_08_24_000004_create_calls_table.php`

**Schema**:

```php
Schema::create('calls', function (Blueprint $table): void {
    $table->bigIncrements('id');
    $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
    $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
    $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
    $table->foreignId('contact_id')->nullable()->constrained('customer_contacts')->nullOnDelete();
    $table->enum('direction', ['inbound', 'outbound'])->default('outbound');
    $table->integer('duration_seconds');
    $table->enum('outcome', ['reached', 'no_answer', 'busy', 'left_voicemail']);
    $table->text('notes')->nullable();
    $table->timestamp('called_at');
    $table->boolean('is_active')->default(true);
    $table->timestamps();
    $table->index(['company_id', 'customer_id']);
    $table->index(['user_id']);
    $table->index(['called_at']);
});
```

**Verification**: `php artisan migrate`

---

### Step 5: Verify rollback

**Command**: `php artisan migrate:rollback`

**Expected**: All 4 tables dropped in reverse order

**Verification**: Check `php artisan migrate:status` shows migrations rolled back

---

### Step 6: Run full test suite

**Command**: `make test`

**Expected**: All existing tests pass

**Verification**: No regressions in existing functionality

---

## Test and Verification

### Migration Tests

```php
// tests/Feature/Migrations/M7MigrationTest.php
it('creates todos table', function () {
    Schema::dropIfExists('todos');
    artisan('migrate');
    expect(Schema::hasTable('todos'))->toBeTrue();
    expect(Schema::hasColumns('todos', ['id', 'company_id', 'user_id', 'title', 'status']))->toBeTrue();
});
```

### Rollback Tests

```php
it('rolls back todos table', function () {
    Schema::dropIfExists('todos');
    artisan('migrate');
    artisan('migrate:rollback');
    expect(Schema::hasTable('todos'))->toBeFalse();
});
```

### Foreign Key Tests

```php
it('enforces foreign key constraints', function () {
    $this->expectException(QueryException::class);
    DB::table('todos')->insert([
        'company_id' => 'non-existent-id',
        'user_id' => Auth::id(),
        'title' => 'Test',
        'status' => 'new',
        'due_date' => now(),
    ]);
});
```

---

## Approval Gates

| Gate | Criteria                     | Owner        | Status  |
| ---- | ---------------------------- | ------------ | ------- |
| G1   | Migration files created      | Backend Lead | Pending |
| G2   | `php artisan migrate` passes | Backend Lead | Pending |
| G3   | Rollback works               | Backend Lead | Pending |
| G4   | Existing tests pass          | QA Lead      | Pending |

---

## Rollback and Recovery

### If Migration Fails

1. Check error message (foreign key violation, column type mismatch, etc.)
2. Fix migration file
3. Run `php artisan migrate:rollback` to undo partial migration
4. Run `php artisan migrate` again

### If Existing Tests Break

1. Identify which test fails
2. Check if migration changed existing table structure
3. Fix migration or update test
4. Re-run `make test`

### If Performance Issues

1. Check query plans with `EXPLAIN`
2. Add or modify indexes
3. Consider partitioning for large tables (calls, ticket_status_history)

---

## Parallel Work

| Task                             | Dependencies | Can Run In Parallel                 |
| -------------------------------- | ------------ | ----------------------------------- |
| Task 1.2: Create Eloquent models | 1.1          | Yes (after migration files created) |
| Task 3.1-3.7: Frontend UI        | None         | Yes (can start immediately)         |
| Task 4.1-4.4: Filament resources | 1.2          | Yes (after models created)          |

---

## Non-Goals

- Creating Eloquent models (Task 1.2)
- Creating service classes (Task 1.3)
- Creating Livewire components (Phase 2)
- Creating Blade templates (Phase 3)
- Seeding demo data

---

## Handoff Prompt

```
Execute Task 1.1: Create migration files for M7 tables.

1. Create 4 migration files in database/migrations/:
   - 2026_08_24_000001_create_todos_table.php
   - 2026_08_24_000002_create_tickets_table.php
   - 2026_08_24_000003_create_ticket_status_history_table.php
   - 2026_08_24_000004_create_calls_table.php

2. Follow existing patterns:
   - Use bigIncrements('id') for primary keys
   - Use foreignId()->constrained()->cascadeOnDelete() for company_id
   - Use enum() for status fields
   - Use timestamps() for created_at/updated_at
   - Add indexes for common query patterns

3. Verify:
   - php artisan migrate passes
   - php artisan migrate:rollback works
   - make test passes (no regressions)

4. Skip visits.is_out_of_route migration (already exists).

Reference: C:\projects\jawla\docs\M7_TASKS.md (Task 1.1)
Reference: C:\projects\jawla\docs\M7_DATA_MODEL.md
```

---

## Recommended Next Skill

**ai-production-feature-builder** — Execute the migration file creation and verify database changes.
