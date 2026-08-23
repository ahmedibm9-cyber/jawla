# PRD: Visit Approval, Batch Assignment, Auto-Status & ROI Tracking

## Users & Roles

- **Admin/Manager** — creates visit plans, approves/rejects, views ROI
- **Sales Rep** — sees only approved visit plans, executes visits

## Problem

Bricks Rep (competitor) offers advance visit planning with manager approval and ROI tracking. Jawla assigns visits one-by-one with no approval gate and has no ROI metric.

## Outcomes

1. Managers submit visit plans → approval workflow → reps see only approved visits
2. Managers batch-assign visits from admin (bulk or route-based)
3. Visit status auto-transitions `pending → completed` when visit report is submitted
4. ROI metric visible on dashboard: `(revenue - expenses) / expenses × 100` per rep

## Scope (Must-Have)

- Visit plan approval workflow (draft → pending_approval → approved/rejected)
- Batch visit assignment from Filament admin
- Auto-status transition on visit report submission
- ROI widget on admin dashboard

## Non-Goals

- Route optimization / Google Maps integration
- Advanced analytics or reporting beyond the ROI widget
- Rep-side visit planning (reps don't create plans)
- Cost-per-visit tracking (expenses are already tracked per-rep, not per-visit)

## Success Measures

- Manager can submit a visit plan and see it go through approval
- Rep's Home page only shows approved assignments
- Bulk assign creates N assignments in one action
- When visit report is submitted, assignment status flips to `completed` automatically
- ROI widget shows per-rep revenue, expenses, and ROI % for a selected period
