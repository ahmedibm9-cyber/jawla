# Milestone 7: Competitor Gap Closure — Risk Register

**Date:** 2026-08-24  
**Status:** Draft

---

## Product Risks

### PR-1: Calendar Complexity Underestimation

**Description**: Calendar with dot indicators and day detail may have edge cases (timezone handling, month boundaries, data aggregation performance).

**Probability**: Medium  
**Impact**: Medium  
**Mitigation**:

- Start with simple month view, add features incrementally
- Use database caching for day-item counts
- Test timezone edge cases early

**Owner**: Product Owner

---

### PR-2: Performance Dashboard Accuracy

**Description**: Metrics like Coverage, Frequency, Call Rate, Plan Achievement depend on accurate data from multiple sources. Calculation errors could lead to wrong business decisions.

**Probability**: Medium  
**Impact**: High  
**Mitigation**:

- Write unit tests for each metric calculation
- Compare manual calculations with automated results
- Add data validation checks
- Include "last calculated" timestamp on dashboard

**Owner**: Tech Lead

---

### PR-3: Offline Sync Conflicts

**Description**: New entities (todos, tickets, requests, calls) add complexity to offline sync. Conflicts may arise when same record modified on multiple devices.

**Probability**: Low  
**Impact**: Medium  
**Mitigation**:

- Use existing sync queue pattern with idempotency keys
- Implement last-write-wins for simple entities
- Add conflict resolution UI for critical data (tickets)
- Test with simulated offline scenarios

**Owner**: Backend Lead

---

### PR-4: Kanban View Performance

**Description**: Kanban view for tickets/requests may be slow with large datasets due to client-side rendering of drag-and-drop.

**Probability**: Low  
**Impact**: Low  
**Mitigation**:

- Paginate kanban columns (max 50 items per column)
- Use lazy loading for card details
- Optimize database queries with proper indexes

**Owner**: Frontend Lead

---

## Security Risks

### SR-1: Permission Escalation

**Description**: New permissions (todos.create, tickets.assign, requests.approve, etc.) may be misconfigured, allowing unauthorized access.

**Probability**: Low  
**Impact**: High  
**Mitigation**:

- Seed default permissions with role assignments
- Add permission checks in all controllers
- Write tests for each permission
- Audit admin role permissions

**Owner**: Security Lead

---

### SR-2: Data Leakage in Exports

**Description**: CSV export for performance dashboard and contacts may expose sensitive data to unauthorized users.

**Probability**: Low  
**Impact**: High  
**Mitigation**:

- Restrict export to managers only
- Add audit log for exports
- Filter sensitive fields from exports
- Validate exported data against permission scope

**Owner**: Security Lead

---

### SR-3: Request Approval Bypass

**Description**: Users may bypass approval workflow by directly manipulating request status via API.

**Probability**: Low  
**Impact**: High  
**Mitigation**:

- Server-side validation of status transitions
- Only managers can approve/reject
- Log all status changes in audit trail
- Test with direct API calls

**Owner**: Backend Lead

---

## Privacy Risks

### PRV-1: Call Recording Consent

**Description**: Logging call duration and notes may require consent in some jurisdictions.

**Probability**: Low  
**Impact**: Medium  
**Mitigation**:

- Add consent notice in call log form
- Store consent timestamp
- Allow users to delete call logs
- Document in privacy policy

**Owner**: Legal

---

### PRV-2: Location Data in Non-Planned Visits

**Description**: Non-planned visits capture GPS location, which may be considered personal data.

**Probability**: Low  
**Impact**: Low  
**Mitigation**:

- Use existing GPS consent from visit flow
- Allow location permission denial
- Store location with minimal precision

**Owner**: Privacy Officer

---

## Migration Risks

### MR-1: Database Migration Failure

**Description**: New migrations may fail on production database due to constraints or data conflicts.

**Probability**: Low  
**Impact**: High  
**Mitigation**:

- Test migrations on production-like data
- Add rollback procedures
- Run migrations during maintenance window
- Backup database before migration

**Owner**: DBA

---

### MR-2: Performance Regression

**Description**: New tables and queries may slow down existing functionality.

**Probability**: Medium  
**Impact**: Medium  
**Mitigation**:

- Add proper indexes
- Test query performance with production data volume
- Monitor slow queries after deployment
- Add query caching where appropriate

**Owner**: Tech Lead

---

## Operational Risks

### OR-1: Increased Support Tickets

**Description**: New features may confuse users, leading to increased support requests.

**Probability**: Medium  
**Impact**: Low  
**Mitigation**:

- Create user documentation
- Add in-app tooltips
- Provide training sessions
- Monitor support ticket volume

**Owner**: Support Lead

---

### OR-2: Mobile Performance

**Description**: New features may slow down PWA on low-end devices.

**Probability**: Medium  
**Impact**: Medium  
**Mitigation**:

- Optimize Blade templates
- Minimize JavaScript
- Use lazy loading for charts
- Test on low-end devices

**Owner**: Frontend Lead

---

## Delivery Risks

### DR-1: Scope Creep

**Description**: Additional features may be requested during implementation, delaying milestone completion.

**Probability**: High  
**Impact**: Medium  
**Mitigation**:

- Strict change control process
- Document all change requests
- Prioritize features against deadline
- Defer non-critical enhancements to M8

**Owner**: Project Manager

---

### DR-2: Resource Availability

**Description**: Key team members may be unavailable during implementation.

**Probability**: Low  
**Impact**: High  
**Mitigation**:

- Cross-train team members
- Document all implementation decisions
- Maintain comprehensive knowledge base
- Have backup resources identified

**Owner**: Project Manager

---

### DR-3: Integration Issues

**Description**: New features may conflict with existing functionality or third-party services.

**Probability**: Low  
**Impact**: Medium  
**Mitigation**:

- Test integration points early
- Use feature flags for gradual rollout
- Monitor error logs after deployment
- Have rollback plan ready

**Owner**: Tech Lead

---

## Risk Matrix

| ID    | Risk                    | Probability | Impact | Score | Owner           |
| ----- | ----------------------- | ----------- | ------ | ----- | --------------- |
| PR-1  | Calendar Complexity     | Medium      | Medium | 4     | Product Owner   |
| PR-2  | Performance Accuracy    | Medium      | High   | 6     | Tech Lead       |
| PR-3  | Offline Sync Conflicts  | Low         | Medium | 3     | Backend Lead    |
| PR-4  | Kanban Performance      | Low         | Low    | 2     | Frontend Lead   |
| SR-1  | Permission Escalation   | Low         | High   | 4     | Security Lead   |
| SR-2  | Data Leakage in Exports | Low         | High   | 4     | Security Lead   |
| SR-3  | Request Approval Bypass | Low         | High   | 4     | Backend Lead    |
| PRV-1 | Call Recording Consent  | Low         | Medium | 3     | Legal           |
| PRV-2 | Location Data Privacy   | Low         | Low    | 2     | Privacy Officer |
| MR-1  | Migration Failure       | Low         | High   | 4     | DBA             |
| MR-2  | Performance Regression  | Medium      | Medium | 4     | Tech Lead       |
| OR-1  | Increased Support       | Medium      | Low    | 3     | Support Lead    |
| OR-2  | Mobile Performance      | Medium      | Medium | 4     | Frontend Lead   |
| DR-1  | Scope Creep             | High        | Medium | 6     | Project Manager |
| DR-2  | Resource Availability   | Low         | High   | 4     | Project Manager |
| DR-3  | Integration Issues      | Low         | Medium | 3     | Tech Lead       |

---

## Risk Response Plan

### High Priority (Score ≥ 6)

1. **PR-2: Performance Accuracy**
   - Action: Create comprehensive test suite for metric calculations
   - Timeline: Week 1-2
   - Owner: Tech Lead

2. **DR-1: Scope Creep**
   - Action: Implement change control process, weekly scope review
   - Timeline: Ongoing
   - Owner: Project Manager

### Medium Priority (Score 4)

1. **PR-1: Calendar Complexity**
   - Action: Prototype calendar early, identify edge cases
   - Timeline: Week 1
   - Owner: Product Owner

2. **SR-1/2/3: Security Risks**
   - Action: Security review of all new endpoints
   - Timeline: Week 3
   - Owner: Security Lead

3. **MR-1: Migration Failure**
   - Action: Test migrations on production-like data
   - Timeline: Week 1
   - Owner: DBA

4. **MR-2: Performance Regression**
   - Action: Load testing with production data volume
   - Timeline: Week 4
   - Owner: Tech Lead

5. **OR-2: Mobile Performance**
   - Action: Test on low-end devices, optimize assets
   - Timeline: Week 3-4
   - Owner: Frontend Lead

6. **DR-2: Resource Availability**
   - Action: Cross-train team, document decisions
   - Timeline: Ongoing
   - Owner: Project Manager

### Low Priority (Score 2-3)

Monitor and address as needed during implementation.

---

## Contingency Plans

### If Performance Dashboard Calculations Are Wrong

1. Disable dashboard temporarily
2. Fix calculation logic
3. Re-run calculations with test data
4. Verify against manual calculations
5. Re-enable dashboard

### If Database Migration Fails

1. Rollback migration
2. Investigate cause
3. Fix migration script
4. Test on staging
5. Retry migration during next maintenance window

### If Offline Sync Causes Data Loss

1. Disable sync temporarily
2. Identify affected records
3. Restore from backup if needed
4. Fix sync logic
5. Re-enable sync with monitoring

### If Security Vulnerability Found

1. Assess impact
2. Patch vulnerability immediately
3. Audit for similar issues
4. Notify affected users if needed
5. Update security documentation
