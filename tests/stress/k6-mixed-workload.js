import http from "k6/http";
import { check, group, sleep } from "k6";

export const options = {
  stages: [
    { duration: "30s", target: 5 },
    { duration: "1m", target: 10 },
    { duration: "2m", target: 20 },
    { duration: "1m", target: 30 },
    { duration: "1m", target: 30 },
    { duration: "30s", target: 5 },
  ],
  thresholds: {
    http_req_duration: ["p(95)<2000"],
    checks: ["rate>0.95"],
  },
};

const BASE_URL = __ENV.BASE_URL || "http://localhost:8000";

const credentials = [
  { email: "admin@jawla.test", password: "password", landing: "/admin" },
  { email: "manager@jawla.test", password: "password", landing: "/admin" },
  { email: "rep@jawla.test", password: "password", landing: "/app" },
  { email: "accounts@jawla.test", password: "password", landing: "/admin" },
  { email: "warehouse@jawla.test", password: "password", landing: "/admin" },
  { email: "executive@jawla.test", password: "password", landing: "/admin" },
];

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

function login(session, cred) {
  const loginPage = http.get(`${BASE_URL}/admin/login`, { jar: session.jar });
  const csrfToken = extractFirst(
    loginPage.body,
    /<meta name="csrf-token" content="([^"]+)"/
  );
  const snapshot = decodeHtml(
    extractFirst(loginPage.body, /wire:snapshot="([^"]+)"/)
  );

  const payload = JSON.stringify({
    _token: csrfToken,
    components: [
      {
        snapshot,
        updates: {
          "data.email": cred.email,
          "data.password": cred.password,
          "data.remember": true,
        },
        calls: [{ path: "", method: "authenticate", params: [] }],
      },
    ],
  });

  return http.post(`${BASE_URL}/livewire/update`, payload, {
    jar: session.jar,
    headers: {
      Accept: "application/json",
      "Content-Type": "application/json",
      "X-CSRF-TOKEN": csrfToken,
      "X-Livewire": "true",
      Referer: `${BASE_URL}/admin/login`,
    },
  });
}

export default function () {
  const cred = credentials[(__VU - 1) % credentials.length];
  const jar = http.cookieJar();

  if (__ITER === 0) {
    const auth = login({ jar }, cred);
    check(auth, {
      "login ok": (r) => r.status === 200,
    });
  }

  group("public health", () => {
    const res = http.get(`${BASE_URL}/up`, { jar });
    check(res, { "up 200": (r) => r.status === 200 });
  });

  group("login page", () => {
    const res = http.get(`${BASE_URL}/admin/login`, { jar });
    check(res, { "login page 200": (r) => r.status === 200 });
  });

  group("landed app page", () => {
    const res = http.get(`${BASE_URL}${cred.landing}`, { jar, redirects: 0 });
    check(res, {
      "landing is reachable": (r) => [200, 302].includes(r.status),
    });
  });

  if (cred.landing === "/app") {
    group("rep pages", () => {
      check(http.get(`${BASE_URL}/app/notifications`, { jar }), {
        "rep notifications ok": (r) => r.status === 200,
      });
      check(http.get(`${BASE_URL}/app/orders`, { jar }), {
        "rep orders ok": (r) => r.status === 200,
      });
      check(http.get(`${BASE_URL}/app/stock`, { jar }), {
        "rep stock ok": (r) => r.status === 200,
      });
    });
  } else {
    group("admin pages", () => {
      const dashboard = http.get(`${BASE_URL}/admin/dashboard`, {
        jar,
        redirects: 0,
      });
      check(dashboard, {
        "admin dashboard reachable": (r) => [200, 302].includes(r.status),
      });
    });
  }

  sleep(Math.random() * 2 + 1);
}

export function handleSummary(data) {
  return {
    stdout: JSON.stringify(data, null, 2),
    "tests/stress/mixed-results.json": JSON.stringify(data, null, 2),
  };
}
