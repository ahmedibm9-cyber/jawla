# Plan Result YAML

```yaml
plan_result:
  milestone: 7
  title: "Competitor Gap Closure"
  status: "ready_for_review"
  created_at: "2026-08-24"

  objectives:
    - "Close 10 feature gaps identified in competitor analysis"
    - "Achieve feature parity with Bricks Rep"
    - "Deliver 9 user-facing PWA features plus 4 admin resources"

  scope:
    included:
      - "Calendar view with visit/todo/ticket indicators"
      - "Todo management (CRUD, priority, due dates)"
      - "Support tickets with status workflow"
      - "Manager approval requests"
      - "Phone call logging"
      - "Non-planned visit recording"
      - "Performance dashboard with 5 tabs"
      - "Daily agenda view"
      - "Contact CSV export"
      - "Filament admin resources for all new entities"
    excluded:
      - "Native mobile app"
      - "Complex workflow engine"
      - "Real-time notifications"
      - "Advanced analytics/BI"
      - "Multi-company switcher"

  effort:
    total_days: 27.5
    parallel_days_2dev: 16
    parallel_days_3dev: 12
    phases:
      - name: "Database Foundation"
        days: 4
        sequential: true
      - name: "Backend API"
        days: 9
        parallel: true
      - name: "Frontend"
        days: 7.5
        parallel: true
      - name: "Admin Resources"
        days: 2
        parallel: true
      - name: "Integration & Testing"
        days: 5
        partial_parallel: true

  data_model:
    new_tables:
      - name: "todos"
        purpose: "Task management"
        key_columns:
          ["company_id", "user_id", "title", "status", "priority", "due_date"]
      - name: "tickets"
        purpose: "Support tickets"
        key_columns:
          [
            "company_id",
            "user_id",
            "customer_id",
            "title",
            "status",
            "priority",
          ]
      - name: "ticket_status_history"
        purpose: "Audit trail"
        key_columns: ["ticket_id", "old_status", "new_status", "changed_by"]
      - name: "requests"
        purpose: "Manager approval"
        key_columns: ["company_id", "user_id", "type", "title", "status"]
      - name: "calls"
        purpose: "Phone call logs"
        key_columns:
          [
            "company_id",
            "user_id",
            "customer_id",
            "direction",
            "duration_seconds",
          ]
    modified_tables:
      - name: "visits"
        change: "Add is_out_of_route boolean column"

  critical_path:
    - "1.1: Create migration files"
    - "1.2: Create Eloquent models"
    - "1.3: Create service classes"
    - "2.5: Performance Dashboard API"
    - "5.2: Offline Sync Integration"
    - "5.5: Feature Tests"
    - "5.6: Browser Tests"

  risks:
    high_priority:
      - id: "PR-2"
        name: "Performance Accuracy"
        mitigation: "Comprehensive test suite for metric calculations"
      - id: "DR-1"
        name: "Scope Creep"
        mitigation: "Change control process, weekly scope review"
    medium_priority:
      - id: "PR-1"
        name: "Calendar Complexity"
        mitigation: "Prototype early, identify edge cases"
      - id: "SR-1"
        name: "Permission Escalation"
        mitigation: "Security review of all new endpoints"
      - id: "MR-1"
        name: "Migration Failure"
        mitigation: "Test migrations on production-like data"
      - id: "MR-2"
        name: "Performance Regression"
        mitigation: "Load testing with production data volume"

  success_criteria:
    functional:
      - "Calendar shows visits, todos, tickets with dot indicators"
      - "Todos can be created and completed"
      - "Tickets flow through status workflow"
      - "Requests can be approved/rejected"
      - "Calls logged with duration and outcome"
      - "Non-planned visits recorded"
      - "Performance dashboard shows all 5 tabs with correct metrics"
      - "Agenda shows daily view"
      - "Contact export works"
    technical:
      - "All tests pass"
      - "RTL works everywhere"
      - "Offline sync works for all new entities"
      - "Performance <3s for dashboard"
      - "No security vulnerabilities"
    business:
      - "Feature parity with Bricks Rep achieved"
      - "No new dependencies added"
      - "Documentation updated"

  documentation:
    - path: "docs/COMPETITOR_GAP_ANALYSIS.md"
      status: "complete"
    - path: "docs/M7_PRD.md"
      status: "complete"
    - path: "docs/M7_SPEC.md"
      status: "complete"
    - path: "docs/M7_USER_JOURNEYS.md"
      status: "complete"
    - path: "docs/M7_DATA_MODEL.md"
      status: "complete"
    - path: "docs/M7_TASKS.md"
      status: "complete"
    - path: "docs/M7_RISKS.md"
      status: "complete"
    - path: "docs/M7_PLAN_SUMMARY.md"
      status: "complete"
    - path: "docs/TASKS.md"
      status: "updated"

  next_steps:
    - "Product Owner reviews all documents"
    - "Stakeholders approve plan"
    - "Assign tasks to developers"
    - "Begin Phase 1 implementation"
    - "Weekly progress reviews"

  open_questions:
    - "Should we use Chart.js for performance dashboard, or CSS-based charts?"
    - "Should kanban view support drag-and-drop for status changes?"
    - "Should Arabic calendar show Hijri dates alongside Gregorian?"
    - "Should we support Excel (XLSX) in addition to CSV?"

  recommendation: "Defer open questions to implementation phase. Start with simplest working version."
```
