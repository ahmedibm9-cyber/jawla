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

function extractFirst(text, pattern) {
  const match = text.match(pattern);
  return match ? match[1] : null;
}

function decodeHtml(value) {
  if (!value) return value;

  return value
    .replace(/&quot;/g, '"')
    .replace(/&#039;/g, "'")
    .replace(/&amp;/g, "&")
    .replace(/&lt;/g, "<")
    .replace(/&gt;/g, ">")
    .replace(/&#123;/g, "{")
    .replace(/&#125;/g, "}");
}

// Login credentials
const credentials =
  __ENV.LOGIN_EMAIL && __ENV.LOGIN_PASSWORD
    ? [{ email: __ENV.LOGIN_EMAIL, password: __ENV.LOGIN_PASSWORD }]
    : [
        { email: "admin@jawla.test", password: "password" },
        { email: "manager@jawla.test", password: "password" },
        { email: "rep@jawla.test", password: "password" },
      ];

export default function () {
  const loginPage = http.get(`${BASE_URL}/admin/login`);
  const csrfToken = extractFirst(
    loginPage.body,
    /<meta name="csrf-token" content="([^"]+)"/
  );
  const snapshot = decodeHtml(
    extractFirst(loginPage.body, /wire:snapshot="([^"]+)"/)
  );

  if (!csrfToken || !snapshot) {
    errorRate.add(1);
    console.error("Failed to get Livewire login payload");
    return;
  }

  // Pick random credential
  const cred = credentials[Math.floor(Math.random() * credentials.length)];

  const payload = JSON.stringify({
    _token: csrfToken,
    components: [
      {
        snapshot,
        updates: {
          "data.email": cred.email,
          "data.password": cred.password,
          "data.remember": false,
        },
        calls: [{ path: "", method: "authenticate", params: [] }],
      },
    ],
  });

  const startTime = Date.now();
  const loginResponse = http.post(`${BASE_URL}/livewire/update`, payload, {
    headers: {
      Accept: "application/json",
      "Content-Type": "application/json",
      "X-CSRF-TOKEN": csrfToken,
      "X-Livewire": "true",
      Referer: `${BASE_URL}/admin/login`,
    },
  });
  loginDuration.add(Date.now() - startTime);

  let redirectUrl = "";
  try {
    redirectUrl = loginResponse.json("components.0.effects.redirect") || "";
  } catch (_) {
    redirectUrl = "";
  }

  const success = check(loginResponse, {
    "login status is 200": (r) => r.status === 200,
    "login redirects to admin": () => redirectUrl.includes("/admin"),
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
