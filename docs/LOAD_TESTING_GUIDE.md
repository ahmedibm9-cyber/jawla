# Jawla — Load Testing Guide

## Prerequisites

- [k6](https://k6.io/) installed (`brew install k6` or `winget install k6`)
- Production URL accessible: `https://jawla.up.railway.app`

## Test Scenarios

### Scenario 1: Concurrent Rep Login (100 users)

Simulates 100 reps logging in simultaneously at shift start.

```bash
k6 run --vus 100 --duration 2m load-tests/01-concurrent-login.js
```

**Expected**: All logins complete within 5s, no 5xx errors.

### Scenario 2: Mixed PWA Traffic (50 users, 5 min)

Simulates normal PWA usage: home page, visit list, customer list, stock check.

```bash
k6 run --vus 50 --duration 5m load-tests/02-mixed-pwa-traffic.js
```

**Expected**: p95 response time < 2s, error rate < 1%.

### Scenario 3: Invoice Creation Spike (20 users, 2 min)

Simulates 20 reps creating invoices simultaneously.

```bash
k6 run --vus 20 --duration 2m load-tests/03-invoice-spike.js
```

**Expected**: No deadlocks, all invoices created, stock correct.

### Scenario 4: Admin Panel Load (10 users, 3 min)

Simulates admin users browsing Filament resources and reports.

```bash
k6 run --vus 10 --duration 3m load-tests/04-admin-panel.js
```

**Expected**: p95 < 3s, no widget loading failures.

## Success Criteria

| Metric             | Target     |
| ------------------ | ---------- |
| p50 response time  | < 500ms    |
| p95 response time  | < 2s       |
| p99 response time  | < 5s       |
| Error rate         | < 1%       |
| Throughput         | > 50 req/s |
| CPU utilization    | < 80%      |
| Memory utilization | < 80%      |

## Monitoring During Tests

```bash
# Watch Railway metrics
railway metrics --service 1fc8121f-7949-44ea-b9b7-df6efb8bd854 --project fbe6b485-38aa-4d9a-b123-bcebda3b81c0

# Watch logs for errors
railway logs --service 1fc8121f-7949-44ea-b9b7-df6efb8bd854 --project fbe6b485-38aa-4d9a-b123-bcebda3b81c0 --lines 50
```

## Scaling Recommendations

Based on test results:

| Users   | Replicas | Notes                    |
| ------- | -------- | ------------------------ |
| 1-50    | 2        | Current config           |
| 50-200  | 3-4      | Add replicas via Railway |
| 200-500 | 4-6      | Consider dedicated DB    |
| 500+    | 6+       | CDN + read replicas      |
