# Milestone 7: Competitor Gap Closure — Data Model

**Date:** 2026-08-24  
**Status:** Draft

---

## New Tables

### todos

**Purpose**: Tracks tasks/todos for representatives

| Column         | Type                        | Constraints                | Description      |
| -------------- | --------------------------- | -------------------------- | ---------------- |
| `id`           | UUID                        | PK                         | Primary key      |
| `company_id`   | UUID                        | FK → companies, NOT NULL   | Tenant isolation |
| `user_id`      | UUID                        | FK → users, NOT NULL       | Assigned rep     |
| `title`        | VARCHAR(255)                | NOT NULL                   | Task title       |
| `description`  | TEXT                        | NULLABLE                   | Task details     |
| `priority`     | ENUM('low','medium','high') | NOT NULL, DEFAULT 'medium' | Priority level   |
| `status`       | ENUM('new','done')          | NOT NULL, DEFAULT 'new'    | Current status   |
| `due_date`     | DATE                        | NOT NULL                   | When task is due |
| `completed_at` | TIMESTAMP                   | NULLABLE                   | When marked done |
| `is_active`    | BOOLEAN                     | NOT NULL, DEFAULT true     | Soft delete      |
| `created_at`   | TIMESTAMP                   | NOT NULL                   | Creation time    |
| `updated_at`   | TIMESTAMP                   | NOT NULL                   | Last update      |
| `created_by`   | UUID                        | FK → users, NULLABLE       | Audit trail      |
| `updated_by`   | UUID                        | FK → users, NULLABLE       | Audit trail      |

**Indexes**:

- `idx_todos_company_user` (company_id, user_id)
- `idx_todos_status` (status)
- `idx_todos_due_date` (due_date)
- `idx_todos_company_active` (company_id, is_active)

**RLS Policy**: `company_id = current_user_company()`

---

### tickets

**Purpose**: Tracks support tickets from reps/managers

| Column        | Type                                                         | Constraints                | Description      |
| ------------- | ------------------------------------------------------------ | -------------------------- | ---------------- |
| `id`          | UUID                                                         | PK                         | Primary key      |
| `company_id`  | UUID                                                         | FK → companies, NOT NULL   | Tenant isolation |
| `user_id`     | UUID                                                         | FK → users, NOT NULL       | Creator          |
| `customer_id` | UUID                                                         | FK → customers, NULLABLE   | Related customer |
| `title`       | VARCHAR(255)                                                 | NOT NULL                   | Ticket title     |
| `description` | TEXT                                                         | NOT NULL                   | Ticket details   |
| `status`      | ENUM('new','in_progress','completed','cancelled','disabled') | NOT NULL, DEFAULT 'new'    | Current status   |
| `priority`    | ENUM('low','medium','high')                                  | NOT NULL, DEFAULT 'medium' | Priority level   |
| `assigned_to` | UUID                                                         | FK → users, NULLABLE       | Assigned manager |
| `resolved_at` | TIMESTAMP                                                    | NULLABLE                   | When resolved    |
| `is_active`   | BOOLEAN                                                      | NOT NULL, DEFAULT true     | Soft delete      |
| `created_at`  | TIMESTAMP                                                    | NOT NULL                   | Creation time    |
| `updated_at`  | TIMESTAMP                                                    | NOT NULL                   | Last update      |
| `created_by`  | UUID                                                         | FK → users, NULLABLE       | Audit trail      |
| `updated_by`  | UUID                                                         | FK → users, NULLABLE       | Audit trail      |

**Indexes**:

- `idx_tickets_company_status` (company_id, status)
- `idx_tickets_company_user` (company_id, user_id)
- `idx_tickets_assigned_to` (assigned_to)
- `idx_tickets_customer` (customer_id)

**RLS Policy**: `company_id = current_user_company()`

---

### ticket_status_history

**Purpose**: Tracks status changes for tickets

| Column       | Type        | Constraints            | Description     |
| ------------ | ----------- | ---------------------- | --------------- |
| `id`         | UUID        | PK                     | Primary key     |
| `ticket_id`  | UUID        | FK → tickets, NOT NULL | Parent ticket   |
| `old_status` | VARCHAR(50) | NULLABLE               | Previous status |
| `new_status` | VARCHAR(50) | NOT NULL               | New status      |
| `changed_by` | UUID        | FK → users, NOT NULL   | Who changed it  |
| `changed_at` | TIMESTAMP   | NOT NULL               | When changed    |
| `notes`      | TEXT        | NULLABLE               | Change notes    |

**Indexes**:

- `idx_ticket_history_ticket` (ticket_id)

---

### requests

**Purpose**: Tracks requests for manager approval

| Column         | Type                                              | Constraints              | Description      |
| -------------- | ------------------------------------------------- | ------------------------ | ---------------- |
| `id`           | UUID                                              | PK                       | Primary key      |
| `company_id`   | UUID                                              | FK → companies, NOT NULL | Tenant isolation |
| `user_id`      | UUID                                              | FK → users, NOT NULL     | Requester        |
| `type`         | ENUM('discount','leave','price_override','other') | NOT NULL                 | Request type     |
| `title`        | VARCHAR(255)                                      | NOT NULL                 | Request title    |
| `description`  | TEXT                                              | NOT NULL                 | Request details  |
| `status`       | ENUM('new','approved','rejected','done')          | NOT NULL, DEFAULT 'new'  | Current status   |
| `reviewed_by`  | UUID                                              | FK → users, NULLABLE     | Who reviewed     |
| `reviewed_at`  | TIMESTAMP                                         | NULLABLE                 | When reviewed    |
| `review_notes` | TEXT                                              | NULLABLE                 | Review notes     |
| `is_active`    | BOOLEAN                                           | NOT NULL, DEFAULT true   | Soft delete      |
| `created_at`   | TIMESTAMP                                         | NOT NULL                 | Creation time    |
| `updated_at`   | TIMESTAMP                                         | NOT NULL                 | Last update      |
| `created_by`   | UUID                                              | FK → users, NULLABLE     | Audit trail      |
| `updated_by`   | UUID                                              | FK → users, NULLABLE     | Audit trail      |

**Indexes**:

- `idx_requests_company_status` (company_id, status)
- `idx_requests_company_user` (company_id, user_id)
- `idx_requests_type` (type)

**RLS Policy**: `company_id = current_user_company()`

---

### calls

**Purpose**: Tracks phone calls with customers

| Column             | Type                                                | Constraints                      | Description            |
| ------------------ | --------------------------------------------------- | -------------------------------- | ---------------------- |
| `id`               | UUID                                                | PK                               | Primary key            |
| `company_id`       | UUID                                                | FK → companies, NOT NULL         | Tenant isolation       |
| `user_id`          | UUID                                                | FK → users, NOT NULL             | Who made/received call |
| `customer_id`      | UUID                                                | FK → customers, NOT NULL         | Customer called        |
| `contact_id`       | UUID                                                | FK → customer_contacts, NULLABLE | Specific contact       |
| `direction`        | ENUM('inbound','outbound')                          | NOT NULL, DEFAULT 'outbound'     | Call direction         |
| `duration_seconds` | INTEGER                                             | NOT NULL                         | Call duration          |
| `outcome`          | ENUM('reached','no_answer','busy','left_voicemail') | NOT NULL                         | Call outcome           |
| `notes`            | TEXT                                                | NULLABLE                         | Call notes             |
| `called_at`        | TIMESTAMP                                           | NOT NULL                         | When call happened     |
| `is_active`        | BOOLEAN                                             | NOT NULL, DEFAULT true           | Soft delete            |
| `created_at`       | TIMESTAMP                                           | NOT NULL                         | Creation time          |
| `updated_at`       | TIMESTAMP                                           | NOT NULL                         | Last update            |

**Indexes**:

- `idx_calls_company_customer` (company_id, customer_id)
- `idx_calls_user` (user_id)
- `idx_calls_called_at` (called_at)

**RLS Policy**: `company_id = current_user_company()`

---

## Modified Tables

### visits

**Add column**:

| Column            | Type    | Constraints             | Description            |
| ----------------- | ------- | ----------------------- | ---------------------- |
| `is_out_of_route` | BOOLEAN | NOT NULL, DEFAULT false | Non-planned visit flag |

**Note**: This column already exists in the DATA_MODEL.md but needs to be verified in migrations.

---

## Relationships Diagram

```
companies
  ├── todos (1:N)
  ├── tickets (1:N)
  ├── requests (1:N)
  └── calls (1:N)

users
  ├── todos (1:N) [user_id]
  ├── tickets (1:N) [user_id]
  ├── tickets (1:N) [assigned_to]
  ├── requests (1:N) [user_id]
  ├── requests (1:N) [reviewed_by]
  └── calls (1:N) [user_id]

customers
  ├── tickets (1:N) [customer_id]
  └── calls (1:N) [customer_id]

customer_contacts
  └── calls (1:N) [contact_id]

tickets
  └── ticket_status_history (1:N)

visits
  └── is_out_of_route (modified column)
```

---

## Migration Files

### Migration 1: Create todos table

```php
Schema::create('todos', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('company_id')->foreign('company_id')->references('id')->on('companies');
    $table->uuid('user_id')->foreign('user_id')->references('id')->on('users');
    $table->string('title', 255);
    $table->text('description')->nullable();
    $table->enum('priority', ['low', 'medium', 'high'])->default('medium');
    $table->enum('status', ['new', 'done'])->default('new');
    $table->date('due_date');
    $table->timestamp('completed_at')->nullable();
    $table->boolean('is_active')->default(true);
    $table->timestamps();
    $table->uuid('created_by')->nullable();
    $table->uuid('updated_by')->nullable();

    $table->index(['company_id', 'user_id']);
    $table->index(['status']);
    $table->index(['due_date']);
    $table->index(['company_id', 'is_active']);
});
```

### Migration 2: Create tickets table

```php
Schema::create('tickets', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('company_id')->foreign('company_id')->references('id')->on('companies');
    $table->uuid('user_id')->foreign('user_id')->references('id')->on('users');
    $table->uuid('customer_id')->nullable()->foreign('customer_id')->references('id')->on('customers');
    $table->string('title', 255);
    $table->text('description');
    $table->enum('status', ['new', 'in_progress', 'completed', 'cancelled', 'disabled'])->default('new');
    $table->enum('priority', ['low', 'medium', 'high'])->default('medium');
    $table->uuid('assigned_to')->nullable()->foreign('assigned_to')->references('id')->on('users');
    $table->timestamp('resolved_at')->nullable();
    $table->boolean('is_active')->default(true);
    $table->timestamps();
    $table->uuid('created_by')->nullable();
    $table->uuid('updated_by')->nullable();

    $table->index(['company_id', 'status']);
    $table->index(['company_id', 'user_id']);
    $table->index(['assigned_to']);
    $table->index(['customer_id']);
});
```

### Migration 3: Create ticket_status_history table

```php
Schema::create('ticket_status_history', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('ticket_id')->foreign('ticket_id')->references('id')->on('tickets');
    $table->string('old_status', 50)->nullable();
    $table->string('new_status', 50);
    $table->uuid('changed_by')->foreign('changed_by')->references('id')->on('users');
    $table->timestamp('changed_at');
    $table->text('notes')->nullable();

    $table->index(['ticket_id']);
});
```

### Migration 4: Create requests table

```php
Schema::create('requests', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('company_id')->foreign('company_id')->references('id')->on('companies');
    $table->uuid('user_id')->foreign('user_id')->references('id')->on('users');
    $table->enum('type', ['discount', 'leave', 'price_override', 'other']);
    $table->string('title', 255);
    $table->text('description');
    $table->enum('status', ['new', 'approved', 'rejected', 'done'])->default('new');
    $table->uuid('reviewed_by')->nullable()->foreign('reviewed_by')->references('id')->on('users');
    $table->timestamp('reviewed_at')->nullable();
    $table->text('review_notes')->nullable();
    $table->boolean('is_active')->default(true);
    $table->timestamps();
    $table->uuid('created_by')->nullable();
    $table->uuid('updated_by')->nullable();

    $table->index(['company_id', 'status']);
    $table->index(['company_id', 'user_id']);
    $table->index(['type']);
});
```

### Migration 5: Create calls table

```php
Schema::create('calls', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('company_id')->foreign('company_id')->references('id')->on('companies');
    $table->uuid('user_id')->foreign('user_id')->references('id')->on('users');
    $table->uuid('customer_id')->foreign('customer_id')->references('id')->on('customers');
    $table->uuid('contact_id')->nullable()->foreign('contact_id')->references('id')->on('customer_contacts');
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

### Migration 6: Add is_out_of_route to visits

```php
Schema::table('visits', function (Blueprint $table) {
    $table->boolean('is_out_of_route')->default(false)->after('purpose');
});
```

---

## Eloquent Models

### Todo Model

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuid;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\BelongsToCompany;

class Todo extends Model
{
    use HasUuid, SoftDeletes, BelongsToCompany;

    protected $fillable = [
        'company_id', 'user_id', 'title', 'description',
        'priority', 'status', 'due_date', 'completed_at',
        'is_active', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'due_date' => 'date',
        'completed_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function complete()
    {
        $this->update([
            'status' => 'done',
            'completed_at' => now(),
        ]);
    }
}
```

### Ticket Model

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuid;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\BelongsToCompany;

class Ticket extends Model
{
    use HasUuid, SoftDeletes, BelongsToCompany;

    protected $fillable = [
        'company_id', 'user_id', 'customer_id', 'title',
        'description', 'status', 'priority', 'assigned_to',
        'resolved_at', 'is_active', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function statusHistory()
    {
        return $this->hasMany(TicketStatusHistory::class);
    }

    public function transitionTo($newStatus, $userId, $notes = null)
    {
        $oldStatus = $this->status;
        $this->update(['status' => $newStatus]);

        $this->statusHistory()->create([
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'changed_by' => $userId,
            'changed_at' => now(),
            'notes' => $notes,
        ]);

        if (in_array($newStatus, ['completed', 'cancelled', 'disabled'])) {
            $this->update(['resolved_at' => now()]);
        }
    }
}
```

### Request Model

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuid;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\BelongsToCompany;

class Request extends Model
{
    use HasUuid, SoftDeletes, BelongsToCompany;

    protected $fillable = [
        'company_id', 'user_id', 'type', 'title',
        'description', 'status', 'reviewed_by', 'reviewed_at',
        'review_notes', 'is_active', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function approve($userId, $notes = null)
    {
        $this->update([
            'status' => 'approved',
            'reviewed_by' => $userId,
            'reviewed_at' => now(),
            'review_notes' => $notes,
        ]);
    }

    public function reject($userId, $reason)
    {
        $this->update([
            'status' => 'rejected',
            'reviewed_by' => $userId,
            'reviewed_at' => now(),
            'review_notes' => $reason,
        ]);
    }
}
```

### Call Model

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuid;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\BelongsToCompany;

class Call extends Model
{
    use HasUuid, SoftDeletes, BelongsToCompany;

    protected $fillable = [
        'company_id', 'user_id', 'customer_id', 'contact_id',
        'direction', 'duration_seconds', 'outcome', 'notes',
        'called_at', 'is_active',
    ];

    protected $casts = [
        'called_at' => 'datetime',
        'duration_seconds' => 'integer',
        'is_active' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function contact()
    {
        return $this->belongsTo(CustomerContact::class, 'contact_id');
    }
}
```

---

## Permissions

Add to existing permission set:

```php
// Todos
'todos.create',
'todos.view',
'todos.update',
'todos.complete',
'todos.delete',

// Tickets
'tickets.create',
'tickets.view',
'tickets.update',
'tickets.assign',
'tickets.close',
'tickets.delete',

// Requests
'requests.create',
'requests.view',
'requests.approve',
'requests.reject',
'requests.delete',

// Calls
'calls.create',
'calls.view',
'calls.delete',

// Performance
'performance.view',
'performance.export',

// Customers (export)
'customers.export',
```

---

## Seed Data

### Default Ticket Statuses

No seed needed — statuses are enum values in the migration.

### Default Request Types

No seed needed — types are enum values in the migration.

### Demo Todos (for development)

```php
Todo::create([
    'company_id' => $companyId,
    'user_id' => $repId,
    'title' => 'Follow up with Ahmed Trading',
    'description' => 'Discuss new product line',
    'priority' => 'high',
    'status' => 'new',
    'due_date' => today(),
]);
```
