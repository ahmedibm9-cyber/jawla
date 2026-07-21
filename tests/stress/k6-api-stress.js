import http from "k6/http";
import { check, sleep, group } from "k6";
import { Rate, Trend } from "k6/metrics";

// Custom metrics
const errorRate = new Rate("errors");
const apiDuration = new Trend("api_duration");

// Stress test configuration
export const options = {
  stages: [
    { duration: "30s", target: 5 }, // Ramp up to 5 users
    { duration: "1m", target: 15 }, // Ramp up to 15 users
    { duration: "2m", target: 30 }, // Ramp up to 30 users (stress)
    { duration: "1m", target: 50 }, // Spike to 50 users
    { duration: "30s", target: 50 }, // Hold at 50 users
    { duration: "1m", target: 10 }, // Ramp down
  ],
  // SLOs (read/page traffic). Pass/fail gates on the BUILT-IN metrics — the real
  // transport error rate (http_req_failed) and the check pass-rate — not the
  // legacy custom `errors` rate, which only counted check failures and misread
  // actual HTTP errors. `errors` is kept below as a diagnostic trend only.
  thresholds: {
    http_req_duration: ["p(95)<1500"], // read p95 SLO: 1.5s (post-runtime-swap baseline was ~0.84s)
    http_req_failed: ["rate<0.05"], // transport error SLO: <5% of requests fail
    checks: ["rate>0.95"], // functional SLO: >95% of assertions pass
    errors: ["rate<0.15"], // diagnostic only (check-failure rate)
  },
};

const BASE_URL = __ENV.BASE_URL || "http://localhost:8000";

export default function () {
  group("Health Check", () => {
    const healthRes = http.get(`${BASE_URL}/up`);
    check(healthRes, {
      "health status is 200": (r) => r.status === 200,
      "health response time < 250ms": (r) => r.timings.duration < 250,
    }) || errorRate.add(1);
  });

  group("Login Page", () => {
    const loginPage = http.get(`${BASE_URL}/admin/login`);
    check(loginPage, {
      "login page status is 200": (r) => r.status === 200,
      "login page has csrf meta": (r) =>
        /<meta name="csrf-token" content="[^"]+"/.test(r.body),
      "login page has livewire snapshot": (r) =>
        /wire:snapshot="[^"]+"/.test(r.body),
      "login page response time < 1000ms": (r) => r.timings.duration < 1000,
    }) || errorRate.add(1);
  });

  group("Offline Page", () => {
    const offlinePage = http.get(`${BASE_URL}/offline`);
    check(offlinePage, {
      "offline page loads": (r) => r.status === 200,
    }) || errorRate.add(1);
  });

  group("Locale Switch", () => {
    const localeRes = http.get(`${BASE_URL}/locale/ar`);
    check(localeRes, {
      "locale switch returns 302 or 200": (r) =>
        r.status === 302 || r.status === 200,
    }) || errorRate.add(1);
  });

  sleep(1);
}

export function handleSummary(data) {
  return {
    stdout: JSON.stringify(data, null, 2),
    "tests/stress/api-results.json": JSON.stringify(data, null, 2),
  };
}
