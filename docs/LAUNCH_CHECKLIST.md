# Jawla — Launch Preparation Checklist

## Pre-Launch (1-2 weeks before)

### Infrastructure

- [ ] Production deployment verified healthy
- [ ] SSL certificate active and valid
- [ ] Custom domain configured (`jawla.up.railway.app`)
- [ ] APP_URL updated in Railway environment
- [ ] Backup procedure tested (restore from backup verified)
- [ ] Monitoring alerts configured (Sentry, Railway metrics)
- [ ] CDN configured for static assets (if needed)

### Data

- [ ] Demo data seeded and verified
- [ ] Production company data imported (if migrating from existing system)
- [ ] User accounts created with correct roles
- [ ] Initial stock levels loaded
- [ ] Price lists configured
- [ ] Routes/territories assigned to reps

### Security

- [ ] All secrets rotated (APP_KEY, DB passwords, API tokens)
- [ ] `.env.production` secrets verified not in git
- [ ] CSP headers configured
- [ ] Rate limiting active on auth endpoints
- [ ] Audit logging enabled
- [ ] Sentry DSN configured for error tracking

### Testing

- [ ] UAT checklist completed (see UAT_CHECKLIST.md)
- [ ] Load testing completed (see LOAD_TESTING_GUIDE.md)
- [ ] All test suites passing
- [ ] Browser compatibility tested (Chrome, Safari, Firefox)
- [ ] Mobile devices tested (iOS Safari, Android Chrome)
- [ ] Offline sync tested end-to-end

### Documentation

- [ ] User guide written (AR + EN)
- [ ] Admin guide written
- [ ] API documentation published
- [ ] Deployment runbook documented
- [ ] Rollback procedure documented

---

## Launch Day

### Go/No-Go Criteria

| Criterion                     | Status | Notes |
| ----------------------------- | ------ | ----- |
| All UAT tests pass            | ☐      |       |
| Load test targets met         | ☐      |       |
| No critical security findings | ☐      |       |
| Monitoring alerts configured  | ☐      |       |
| Rollback procedure tested     | ☐      |       |
| User training completed       | ☐      |       |
| Stakeholder sign-off obtained | ☐      |       |

### Launch Steps

1. **T-1h**: Final deployment verification

   ```bash
   curl -sf https://jawla.up.railway.app/up
   ```

2. **T-30m**: Notify team of launch window

3. **T-0**: Enable production mode
   - Set `JAWLA_MODE=production` in Railway
   - Verify demo banner removed

4. **T+5m**: Verify production login works
   - Test admin login
   - Test rep login
   - Verify GPS check-in works

5. **T+15m**: Monitor for errors

   ```bash
   railway logs --service <service-id> --lines 100
   ```

6. **T+1h**: Confirm stable operation

---

## Post-Launch (first week)

- [ ] Daily health check monitoring
- [ ] User feedback collection
- [ ] Bug triage and hotfix process
- [ ] Performance metrics baseline established
- [ ] Backup verification
- [ ] Documentation updates based on user feedback

---

## Rollback Procedure

If critical issues arise:

1. **Immediate**: Set `JAWLA_MODE=maintenance` in Railway (shows maintenance page)
2. **Rollback**: Deploy previous known-good commit
   ```bash
   railway redeploy --id <previous-deployment-id>
   ```
3. **Verify**: Health check passes
4. **Communicate**: Notify users of issue and ETA

---

## Contact Matrix

| Role           | Name | Phone | Email |
| -------------- | ---- | ----- | ----- |
| Technical Lead |      |       |       |
| DevOps         |      |       |       |
| Product Owner  |      |       |       |
| QA Lead        |      |       |       |
