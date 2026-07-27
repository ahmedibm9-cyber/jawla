# Feature: [Name]

## Problem

What user or business problem does this solve?

## User outcome

What does the user see or experience when this is done?

## In scope

- Item 1
- Item 2

## Out of scope

- Item 1
- Item 2

## User roles

Which roles interact with this feature? (rep, sales_manager, warehouse_keeper, hr_admin, system_viewer)

## Functional requirements

1. Given [context], when [action], then [outcome].
2. ...

## Business rules

Reference `docs/BUSINESS_RULES.md` for non-negotiables. List any feature-specific rules:

- Rule 1
- Rule 2

## Validation rules

| Field | Rule | Error message (AR/EN) |
| ----- | ---- | --------------------- |
|       |      |                       |

## Permissions

| Action | Allowed roles |
| ------ | ------------- |
|        |               |

## Error cases

- What happens when [failure scenario]?
- What happens when [edge case]?

## Data changes

New tables, columns, or relationships. Migration strategy.

## API changes

New or modified endpoints. Link to OpenAPI spec if applicable.

## UI states

- Loading
- Empty
- Error
- Success

## Accessibility requirements

- Keyboard navigation
- Screen reader labels
- Color contrast

## Observability requirements

- What is logged?
- What metrics are emitted?
- What alerts fire?

## Acceptance criteria

- [ ] Criterion 1
- [ ] Criterion 2
- [ ] Criterion 3

## Rollback considerations

- Can this migration be reversed?
- What data cleanup is needed on rollback?
