import http from "k6/http";
import { check, sleep } from "k6";
import { Rate, Trend } from "k6/metrics";

// Custom metrics
const errorRate = new Rate("errors");
const loginDuration = new Trend("login_duration");

// Stress test configuration
export const options = {
  stages: [
    { duration: "30s", target: 10 }, // Ramp up to 10 users
    { duration: "1m", target: 20 }, // Ramp up to 20 users
    { duration: "2m", target: 50 }, // Ramp up to 50 users (stress)
    { duration: "1m", target: 100 }, // Spike to 100 users
    { duration: "30s", target: 100 }, // Hold at 100 users
    { duration: "1m", target: 20 }, // Ramp down
  ],
  thresholds: {
    http_req_duration: ["p(95)<2000"], // 95% of requests under 2s
    errors: ["rate<0.1"], // Error rate under 10%
  },
};

const BASE_URL = __ENV.BASE_URL || "http://localhost:8000";

// Login credentials
const credentials = [
  { email: "admin@jawla.test", password: "password" },
  { email: "manager@jawla.test", password: "password" },
  { email: "rep@jawla.test", password: "password" },
];

export default function () {
  // Get CSRF token
  const loginPage = http.get(`${BASE_URL}/admin/login`);
  const csrfToken = loginPage.html('input[name="_token"]');

  if (!csrfToken) {
    errorRate.add(1);
    console.error("Failed to get CSRF token");
    return;
  }

  // Pick random credential
  const cred = credentials[Math.floor(Math.random() * credentials.length)];

  // Login request
  const startTime = Date.now();
  const loginResponse = http.post(
    `${BASE_URL}/admin/login`,
    {
      _token: csrfToken,
      email: cred.email,
      password: cred.password,
    },
    {
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
      },
    }
  );
  loginDuration.add(Date.now() - startTime);

  // Check response
  const success = check(loginResponse, {
    "login status is 200 or 302": (r) => r.status === 200 || r.status === 302,
    "login response time < 2000ms": (r) => r.timings.duration < 2000,
  });

  if (!success) {
    errorRate.add(1);
  }

  sleep(1);
}

export function handleSummary(data) {
  return {
    stdout: JSON.stringify(data, null, 2),
    "tests/stress/login-results.json": JSON.stringify(data, null, 2),
  };
}
